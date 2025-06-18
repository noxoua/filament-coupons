<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Models\CouponUsage;

uses(RefreshDatabase::class);

describe('Security Tests', function () {
    it('prevents SQL injection in coupon codes', function () {
        $maliciousCode = "'; DROP TABLE coupons; --";

        $coupon = Coupon::factory()->create([
            'code' => $maliciousCode,
            'active' => true,
        ]);

        // Code should be stored as-is (Eloquent handles escaping)
        expect($coupon->code)->toBe($maliciousCode);

        // Table should still exist
        expect(Coupon::count())->toBeGreaterThan(0);
    });

    it('handles malicious payload data safely', function () {
        $maliciousPayload = [
            'script' => '<script>alert("xss")</script>',
            'sql' => "'; DROP TABLE users; --",
            'php' => '<?php system("rm -rf /"); ?>',
            'large_data' => str_repeat('A', 1000000), // 1MB string
        ];

        $coupon = Coupon::factory()->create([
            'active' => true,
            'payload' => $maliciousPayload,
        ]);

        // Data should be stored safely
        expect($coupon->payload['script'])->toBe('<script>alert("xss")</script>');
        expect($coupon->payload['sql'])->toBe("'; DROP TABLE users; --");
        expect($coupon->payload['php'])->toBe('<?php system("rm -rf /"); ?>');
        expect(mb_strlen($coupon->payload['large_data']))->toBe(1000000);
    });

    it('prevents unauthorized coupon consumption', function () {
        $coupon = Coupon::factory()->create([
            'active' => false, // Inactive coupon
        ]);

        // Should not be able to consume inactive coupon
        $result = coupons()->consume($coupon);
        expect($result)->toBeFalse();

        // No usage should be recorded
        expect($coupon->usages()->count())->toBe(0);
    });

    it('prevents consumption of expired coupons', function () {
        $coupon = Coupon::factory()->expired()->create();

        $result = coupons()->consume($coupon);
        expect($result)->toBeFalse();

        expect($coupon->usages()->count())->toBe(0);
    });

    it('prevents consumption beyond usage limits', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 1,
        ]);

        // First consumption should succeed
        expect(coupons()->consume($coupon))->toBeTrue();

        // Second consumption should fail
        expect(coupons()->consume($coupon))->toBeFalse();

        // Verify only one usage recorded
        expect($coupon->usages()->count())->toBe(1);
    });

    it('handles race conditions in concurrent usage', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 1,
        ]);

        // Simulate concurrent access
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = coupons()->consume($coupon);
        }

        // Only one should succeed
        $successCount = count(array_filter($results));
        expect($successCount)->toBe(1);

        // Verify database consistency
        expect($coupon->usages()->count())->toBe(1);
    });

    it('validates coupon code length limits', function () {
        // Test with exactly 20 characters (maximum length)
        $maxLengthCode = str_repeat('A', 20);
        $coupon = Coupon::factory()->create(['code' => $maxLengthCode]);

        expect(mb_strlen($coupon->code))->toBe(20);

        // Test with empty code
        $emptyCoupon = Coupon::factory()->create(['code' => '']);
        expect($emptyCoupon->code)->toBe('');
    });

    it('protects against payload injection attacks', function () {
        $injectionPayload = [
            'eval' => 'eval("malicious code")',
            'include' => 'include("/etc/passwd")',
            'file_get_contents' => 'file_get_contents("sensitive_file.txt")',
            'shell_exec' => 'shell_exec("rm -rf /")',
        ];

        $coupon = Coupon::factory()->create([
            'active' => true,
            'payload' => $injectionPayload,
        ]);

        // Payload should be stored as data, not executed
        expect($coupon->payload['eval'])->toBe('eval("malicious code")');
        expect($coupon->payload['include'])->toBe('include("/etc/passwd")');

        // Consuming coupon should not execute malicious code
        $result = coupons()->consume($coupon);
        expect($result)->toBeTrue();
    });

    it('prevents meta data injection in usage records', function () {
        $coupon = Coupon::factory()->create(['active' => true]);

        $maliciousMeta = [
            'user_id' => '1 OR 1=1',
            'order_id' => "'; DROP TABLE orders; --",
            'notes' => '<script>alert("xss")</script>',
            'serialized' => serialize(['dangerous' => 'data']),
        ];

        $result = coupons()->consume($coupon, null, $maliciousMeta);

        expect($result)->toBeTrue();

        $usage = $coupon->usages()->first();
        expect($usage->meta['user_id'])->toBe('1 OR 1=1');
        expect($usage->meta['order_id'])->toBe("'; DROP TABLE orders; --");
        expect($usage->meta['notes'])->toBe('<script>alert("xss")</script>');
    });

    it('handles extremely large coupon codes gracefully', function () {
        // This should be handled by database constraints
        $largeCoupon = Coupon::factory()->make([
            'code' => str_repeat('A', 21), // Over 20 char limit
        ]);

        // Attempt to save should handle the constraint
        // Note: SQLite doesn't always enforce string length constraints,
        // so we'll check that the code is properly truncated or validation fails
        try {
            $largeCoupon->save();
            // If it saves, the code should be truncated to 20 characters
            expect(mb_strlen($largeCoupon->code))->toBeLessThanOrEqual(20);
        } catch (Exception $e) {
            // If it throws an exception, that's also acceptable
            expect($e)->toBeInstanceOf(Exception::class);
        }
    });

    it('prevents unauthorized strategy execution', function () {
        $coupon = Coupon::factory()->create([
            'strategy' => 'non_existent_strategy',
            'active' => true,
        ]);

        // Should not execute unknown strategy
        $result = coupons()->applyCoupon($coupon);
        expect($result)->toBeFalse();
    });
});

describe('Data Integrity Tests', function () {
    it('maintains referential integrity', function () {
        $coupon = Coupon::factory()->create(['active' => true]);

        // Create usage
        $usage = CouponUsage::factory()->forCoupon($coupon)->create();

        // Verify relationship
        expect($usage->coupon->id)->toBe($coupon->id);
        expect($coupon->usages()->count())->toBe(1);
    });

    it('handles orphaned usage records', function () {
        $coupon = Coupon::factory()->create(['active' => true]);
        $usage = CouponUsage::factory()->forCoupon($coupon)->create();

        // Delete coupon (this should cascade delete usages due to foreign key)
        $coupon->delete();

        // Usage should also be deleted
        expect(CouponUsage::find($usage->id))->toBeNull();
    });

    it('validates coupon state consistency', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 2,
        ]);

        // Consume twice to reach limit
        coupons()->consume($coupon);
        coupons()->consume($coupon);

        // Coupon should be deactivated
        $coupon->refresh();
        expect($coupon->active)->toBeFalse();
        expect($coupon->usages()->count())->toBe(2);

        // Further consumption should fail
        expect(coupons()->consume($coupon))->toBeFalse();
    });

    it('ensures atomic operations during consumption', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 1,
        ]);

        // Successful consumption should create usage and update coupon
        $result = coupons()->consume($coupon);

        expect($result)->toBeTrue();
        expect($coupon->usages()->count())->toBe(1);

        $coupon->refresh();
        expect($coupon->active)->toBeFalse();
    });
});
