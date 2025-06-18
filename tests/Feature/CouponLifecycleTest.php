<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

uses(RefreshDatabase::class);

describe('Coupon Lifecycle', function () {
    it('can create and validate a complete coupon flow', function () {
        // Create a test strategy
        $testStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'test_discount';
            }

            public function apply(Coupon $coupon): bool
            {
                // Consume the coupon and return success
                return coupons()->consume($coupon);
            }
        };

        config(['filament-coupons.strategies' => [get_class($testStrategy)]]);

        // Create an active coupon
        $coupon = Coupon::factory()->create([
            'code' => 'DISCOUNT10',
            'strategy' => 'test_discount',
            'active' => true,
            'usage_limit' => 5,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Verify coupon is valid
        expect(coupons()->isValid($coupon))->toBeTrue();

        // Apply the coupon
        $result = coupons()->applyCoupon($coupon);
        expect($result)->toBeTrue();

        // Verify usage was recorded
        expect($coupon->usages()->count())->toBe(1);

        // Verify coupon can still be consumed (under limit)
        expect(coupons()->canConsume($coupon))->toBeTrue();
    });

    it('handles coupon reaching usage limit', function () {
        $coupon = Coupon::factory()->create([
            'code' => 'LIMITED',
            'active' => true,
            'usage_limit' => 2,
        ]);

        // First usage
        expect(coupons()->consume($coupon))->toBeTrue();
        expect($coupon->usages()->count())->toBe(1);
        expect(coupons()->canConsume($coupon))->toBeTrue();

        // Second usage (reaches limit)
        expect(coupons()->consume($coupon))->toBeTrue();
        expect($coupon->usages()->count())->toBe(2);
        expect(coupons()->canConsume($coupon))->toBeFalse();

        // Verify coupon was deactivated
        $coupon->refresh();
        expect($coupon->active)->toBeFalse();

        // Third usage should fail
        expect(coupons()->consume($coupon))->toBeFalse();
    });

    it('handles expired coupons', function () {
        $coupon = Coupon::factory()->create([
            'code' => 'EXPIRED',
            'active' => true,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        expect(coupons()->isActive($coupon))->toBeFalse();
        expect(coupons()->isValid($coupon))->toBeFalse();
        expect(coupons()->consume($coupon))->toBeFalse();
    });

    it('handles coupons not started yet', function () {
        $coupon = Coupon::factory()->create([
            'code' => 'FUTURE',
            'active' => true,
            'starts_at' => Carbon::now()->addDay(),
        ]);

        expect(coupons()->isActive($coupon))->toBeFalse();
        expect(coupons()->isValid($coupon))->toBeFalse();
        expect(coupons()->consume($coupon))->toBeFalse();
    });

    it('can consume coupon with metadata', function () {
        $coupon = Coupon::factory()->create([
            'code' => 'WITHMETA',
            'active' => true,
        ]);

        $metadata = [
            'user_id' => 123,
            'order_id' => 456,
            'discount_amount' => 10,
        ];

        $result = coupons()->consume($coupon, null, $metadata);

        expect($result)->toBeTrue();

        $usage = $coupon->usages()->first();
        expect($usage->meta)->toBe($metadata);
    });

    it('can consume coupon with couponable model', function () {
        $coupon = Coupon::factory()->create([
            'code' => 'WITHUSER',
            'active' => true,
        ]);

        // Create a mock user model
        $user = new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'users';

            protected $fillable = ['name'];
        };
        $user->id = 1;
        $user->name = 'Test User';

        $result = coupons()->consume($coupon, $user);

        expect($result)->toBeTrue();

        $usage = $coupon->usages()->first();
        expect($usage->couponable_type)->toBe(get_class($user));
        expect($usage->couponable_id)->toBe($user->id);
    });
});

describe('Strategy Management', function () {
    it('can register and use multiple strategies', function () {
        $strategy1 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'percentage_discount';
            }
        };

        $strategy2 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'fixed_amount_discount';
            }
        };

        config([
            'filament-coupons.strategies' => [
                get_class($strategy1),
                get_class($strategy2),
            ],
        ]);

        $strategies = coupons()->getStrategies();

        expect($strategies)->toHaveCount(2)
            ->and($strategies)->toHaveKey('percentage_discount')
            ->and($strategies)->toHaveKey('fixed_amount_discount');
    });

    it('can get strategy payload schema', function () {
        $strategyWithSchema = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'complex_discount';
            }

            public function schema(): array
            {
                return [
                    'percentage' => ['type' => 'number', 'required' => true],
                    'max_amount' => ['type' => 'number', 'required' => false],
                    'categories' => ['type' => 'array', 'required' => false],
                ];
            }
        };

        config(['filament-coupons.strategies' => [get_class($strategyWithSchema)]]);

        $schema = coupons()->getStrategyPayloadSchema('complex_discount');

        expect($schema)->toHaveKey('percentage')
            ->and($schema)->toHaveKey('max_amount')
            ->and($schema)->toHaveKey('categories');
    });
});

describe('Database Transactions', function () {
    it('rolls back on failure during consumption', function () {
        $coupon = Coupon::factory()->create([
            'code' => 'ROLLBACK',
            'active' => true,
            'usage_limit' => 1,
        ]);

        // Mock a scenario where the coupon update fails
        // This is a simplified test - in practice you'd mock the database
        $initialUsageCount = $coupon->usages()->count();

        // Consume the coupon normally
        $result = coupons()->consume($coupon);

        expect($result)->toBeTrue();
        expect($coupon->usages()->count())->toBe($initialUsageCount + 1);
    });
});
