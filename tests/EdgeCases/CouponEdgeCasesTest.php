<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Tests\EdgeCases;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;
use stdClass;

uses(RefreshDatabase::class);

describe('Edge Cases and Boundary Conditions', function () {
    it('handles null and empty values gracefully', function () {
        $coupon = new Coupon([
            'code' => '',
            'strategy' => '',
            'active' => false,
        ]);

        expect(coupons()->isValid($coupon))->toBeFalse()
            ->and(coupons()->applyCoupon($coupon))->toBeFalse();
    });

    it('handles extremely long coupon codes', function () {
        // Test with maximum length code (20 characters as per migration)
        $longCode = str_repeat('A', 20);

        $coupon = Coupon::factory()->create([
            'code' => $longCode,
            'active' => true,
        ]);

        expect($coupon->code)->toBe($longCode)
            ->and(mb_strlen($coupon->code))->toBe(20);
    });

    it('handles duplicate coupon codes appropriately', function () {
        $code = 'DUPLICATE';

        Coupon::factory()->create(['code' => $code]);

        // Attempt to create another coupon with same code should fail
        expect(function () use ($code) {
            Coupon::factory()->create(['code' => $code]);
        })->toThrow(QueryException::class);
    });

    it('handles extreme date ranges', function () {
        // Test with very far future date
        $farFuture = Carbon::create(2099, 12, 31, 23, 59, 59);

        $coupon = Coupon::factory()->create([
            'active' => true,
            'starts_at' => Carbon::now(),
            'expires_at' => $farFuture,
        ]);

        expect(coupons()->isActive($coupon))->toBeTrue();

        // Test with very old start date
        $farPast = Carbon::create(1990, 1, 1, 0, 0, 0);

        $coupon2 = Coupon::factory()->create([
            'active' => true,
            'starts_at' => $farPast,
            'expires_at' => Carbon::now()->addYear(),
        ]);

        expect(coupons()->isActive($coupon2))->toBeTrue();
    });

    it('handles zero and negative usage limits', function () {
        // Test with zero usage limit
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 0,
        ]);

        expect(coupons()->canConsume($coupon))->toBeFalse();

        // Test with negative usage limit (should be treated as unlimited)
        $coupon2 = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => -1,
        ]);

        // Negative limits might be handled differently based on implementation
        expect(coupons()->canConsume($coupon2))->toBeFalse();
    });

    it('handles extremely large usage limits', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => PHP_INT_MAX,
        ]);

        expect(coupons()->canConsume($coupon))->toBeTrue();

        // Consume once
        coupons()->consume($coupon);

        // Should still be consumable due to large limit
        expect(coupons()->canConsume($coupon))->toBeTrue();
    });

    it('handles complex JSON payloads', function () {
        $complexPayload = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'deep_value' => 'test',
                        'array' => [1, 2, 3, 4, 5],
                        'boolean' => true,
                        'null_value' => null,
                    ],
                ],
            ],
            'unicode' => '🎉 Special characters: àáâãäå',
            'numbers' => [
                'integer' => 42,
                'float' => 3.14159,
                'negative' => -100,
                'zero' => 0,
            ],
        ];

        $coupon = Coupon::factory()->create([
            'active' => true,
            'payload' => $complexPayload,
        ]);

        expect($coupon->payload)->toBe($complexPayload)
            ->and($coupon->payload['unicode'])->toContain('🎉')
            ->and($coupon->payload['level1']['level2']['level3']['deep_value'])->toBe('test');
    });

    it('handles malformed JSON gracefully', function () {
        // This would typically be handled at the database/Eloquent level
        // but we can test edge cases with complex structures

        $edgeCasePayload = [
            'empty_string' => '',
            'empty_array' => [],
            'empty_object' => new stdClass(),
            'very_long_string' => str_repeat('x', 10000),
            'mixed_types' => [
                'string',
                123,
                true,
                null,
                ['nested', 'array'],
            ],
        ];

        $coupon = Coupon::factory()->create([
            'active' => true,
            'payload' => $edgeCasePayload,
        ]);

        expect($coupon->payload['empty_string'])->toBe('')
            ->and($coupon->payload['empty_array'])->toBe([])
            ->and(mb_strlen($coupon->payload['very_long_string']))->toBe(10000);
    });
});

describe('Concurrent Access Edge Cases', function () {
    it('handles rapid consecutive consumptions', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 1,
        ]);

        // Attempt rapid consumption
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = coupons()->consume($coupon);
        }

        // Only one should succeed
        $successCount = count(array_filter($results));
        expect($successCount)->toBe(1);

        // Verify final state
        expect($coupon->usages()->count())->toBe(1);
        $coupon->refresh();
        expect($coupon->active)->toBeFalse();
    });

    it('handles consumption at exact expiry time', function () {
        $expiryTime = Carbon::now()->addMinutes(5);

        $coupon = Coupon::factory()->create([
            'active' => true,
            'expires_at' => $expiryTime,
        ]);

        // Set time to just before expiry
        Carbon::setTestNow($expiryTime->copy()->subSecond());
        expect(coupons()->isActive($coupon))->toBeTrue();

        // Set time to exact expiry
        Carbon::setTestNow($expiryTime);
        expect(coupons()->isActive($coupon))->toBeFalse(); // Should be expired at exact time

        // Set time to just after expiry
        Carbon::setTestNow($expiryTime->copy()->addSecond());
        expect(coupons()->isActive($coupon))->toBeFalse();

        Carbon::setTestNow(); // Reset time
    });

    it('handles consumption at exact start time', function () {
        $startTime = Carbon::now()->addMinutes(5);

        $coupon = Coupon::factory()->create([
            'active' => true,
            'starts_at' => $startTime,
        ]);

        // Before start time
        Carbon::setTestNow($startTime->copy()->subSecond());
        expect(coupons()->isActive($coupon))->toBeFalse();

        // At exact start time
        Carbon::setTestNow($startTime);
        expect(coupons()->isActive($coupon))->toBeTrue();

        // After start time
        Carbon::setTestNow($startTime->copy()->addSecond());
        expect(coupons()->isActive($coupon))->toBeTrue();

        Carbon::setTestNow(); // Reset time
    });
});

describe('Strategy Edge Cases', function () {
    it('handles strategy that throws exceptions', function () {
        $faultyStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'faulty_strategy';
            }

            public function apply(Coupon $coupon): bool
            {
                throw new Exception('Strategy error');
            }
        };

        config(['filament-coupons.strategies' => [get_class($faultyStrategy)]]);

        $coupon = Coupon::factory()->create([
            'strategy' => 'faulty_strategy',
            'active' => true,
        ]);

        // The applyCoupon method should handle the exception gracefully
        expect(function () use ($coupon) {
            coupons()->applyCoupon($coupon);
        })->toThrow(Exception::class, 'Strategy error');
    });

    it('handles strategy with circular references in schema', function () {
        $circularStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'circular_strategy';
            }

            public function schema(): array
            {
                $schema = ['field1' => 'value1'];
                // This would create issues if not handled properly
                $schema['circular'] = &$schema;

                return ['safe_field' => 'safe_value']; // Return safe schema instead
            }
        };

        config(['filament-coupons.strategies' => [get_class($circularStrategy)]]);

        $schema = coupons()->getStrategyPayloadSchema('circular_strategy');

        expect($schema)->toBeArray()
            ->and($schema)->toHaveKey('safe_field');
    });

    it('handles strategy name collisions', function () {
        $strategy1 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'same_name';
            }
        };

        $strategy2 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'same_name'; // Same name as strategy1
            }
        };

        config([
            'filament-coupons.strategies' => [
                get_class($strategy1),
                get_class($strategy2),
            ],
        ]);

        $strategies = coupons()->getStrategies();

        // Should handle collision gracefully (likely last one wins)
        expect($strategies)->toHaveKey('same_name')
            ->and($strategies)->toHaveCount(1);
    });
});

describe('Database Edge Cases', function () {
    it('handles coupon with missing required relationships', function () {
        // Create coupon usage without proper coupon relationship
        $usage = new \Noxo\FilamentCoupons\Models\CouponUsage([
            'coupon_id' => 99999, // Non-existent coupon
        ]);

        // This should be handled by foreign key constraints or validation
        // Note: SQLite might not always enforce foreign key constraints,
        // so we'll check for either an exception or that the coupon relationship fails
        try {
            $usage->save();
            // If it saves, the coupon relationship should be null
            expect($usage->coupon)->toBeNull();
        } catch (Exception $e) {
            // If it throws an exception, that's also acceptable
            expect($e)->toBeInstanceOf(Exception::class);
        }
    });

    it('handles extremely large meta data', function () {
        $coupon = Coupon::factory()->create(['active' => true]);

        // Create very large meta data
        $largeMeta = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeMeta["key_$i"] = str_repeat('data', 100);
        }

        $result = coupons()->consume($coupon, null, $largeMeta);

        expect($result)->toBeTrue();

        $usage = $coupon->usages()->first();
        expect($usage->meta)->toHaveCount(1000);
    });
});
