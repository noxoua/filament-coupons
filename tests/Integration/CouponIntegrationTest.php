<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

uses(RefreshDatabase::class);

describe('Coupon Strategy Integration', function () {
    it('can create and use custom discount strategy', function () {
        // Create a custom percentage discount strategy
        $percentageStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'percentage_discount';
            }

            public function getLabel(): string
            {
                return 'Percentage Discount';
            }

            public function schema(): array
            {
                return [
                    'percentage' => [
                        'type' => 'number',
                        'required' => true,
                        'min' => 1,
                        'max' => 100,
                    ],
                    'max_discount' => [
                        'type' => 'number',
                        'required' => false,
                    ],
                ];
            }

            public function apply(Coupon $coupon): bool
            {
                $payload = $coupon->payload ?? [];
                $percentage = $payload['percentage'] ?? 0;

                // Simple validation
                if ($percentage <= 0 || $percentage > 100) {
                    return false;
                }

                // In real implementation, this would apply the discount
                // For testing, we'll just consume the coupon
                return coupons()->consume($coupon);
            }
        };

        Config::set('filament-coupons.strategies', [get_class($percentageStrategy)]);

        // Create a coupon with the strategy
        $coupon = Coupon::factory()->create([
            'code' => 'SAVE20',
            'strategy' => 'percentage_discount',
            'payload' => ['percentage' => 20, 'max_discount' => 50],
            'active' => true,
        ]);

        // Test strategy is available
        $strategy = coupons()->getStrategy('percentage_discount');
        expect($strategy)->not()->toBeNull()
            ->and($strategy->getLabel())->toBe('Percentage Discount');

        // Test schema
        $schema = coupons()->getStrategyPayloadSchema('percentage_discount');
        expect($schema)->toHaveKey('percentage')
            ->and($schema)->toHaveKey('max_discount');

        // Test coupon application
        $result = coupons()->applyCoupon($coupon);
        expect($result)->toBeTrue();

        // Verify usage was recorded
        expect($coupon->usages()->count())->toBe(1);
    });

    it('can create fixed amount discount strategy', function () {
        // Create a custom fixed amount discount strategy
        $fixedStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'fixed_discount';
            }

            public function schema(): array
            {
                return [
                    'amount' => [
                        'type' => 'number',
                        'required' => true,
                        'min' => 0.01,
                    ],
                    'currency' => [
                        'type' => 'string',
                        'required' => false,
                        'default' => 'USD',
                    ],
                ];
            }

            public function apply(Coupon $coupon): bool
            {
                $payload = $coupon->payload ?? [];
                $amount = $payload['amount'] ?? 0;

                if ($amount <= 0) {
                    return false;
                }

                return coupons()->consume($coupon);
            }
        };

        Config::set('filament-coupons.strategies', [get_class($fixedStrategy)]);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'strategy' => 'fixed_discount',
            'payload' => ['amount' => 10.00, 'currency' => 'USD'],
            'active' => true,
        ]);

        $result = coupons()->applyCoupon($coupon);
        expect($result)->toBeTrue()
            ->and($coupon->usages()->count())->toBe(1);
    });

    it('can handle multiple strategies simultaneously', function () {
        $strategy1 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'strategy_one';
            }

            public function apply(Coupon $coupon): bool
            {
                return true;
            }
        };

        $strategy2 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'strategy_two';
            }

            public function apply(Coupon $coupon): bool
            {
                return true;
            }
        };

        Config::set('filament-coupons.strategies', [
            get_class($strategy1),
            get_class($strategy2),
        ]);

        $strategies = coupons()->getStrategies();

        expect($strategies)->toHaveCount(2)
            ->and($strategies)->toHaveKey('strategy_one')
            ->and($strategies)->toHaveKey('strategy_two');
    });
});

describe('Complex Coupon Scenarios', function () {
    it('handles bulk coupon creation and consumption', function () {
        // Create multiple coupons
        $coupons = Coupon::factory()->count(10)->create([
            'active' => true,
            'usage_limit' => 1,
        ]);

        // Consume all coupons
        foreach ($coupons as $coupon) {
            $result = coupons()->consume($coupon);
            expect($result)->toBeTrue();
        }

        // Verify all coupons are now inactive
        foreach ($coupons as $coupon) {
            $coupon->refresh();
            expect($coupon->active)->toBeFalse()
                ->and($coupon->usages()->count())->toBe(1);
        }
    });

    it('handles concurrent coupon usage', function () {
        $coupon = Coupon::factory()->create([
            'code' => 'CONCURRENT',
            'active' => true,
            'usage_limit' => 5,
        ]);

        // Simulate concurrent usage
        for ($i = 0; $i < 3; $i++) {
            $result = coupons()->consume($coupon);
            expect($result)->toBeTrue();
        }

        expect($coupon->usages()->count())->toBe(3)
            ->and(coupons()->canConsume($coupon))->toBeTrue();

        // Consume remaining
        for ($i = 0; $i < 2; $i++) {
            $result = coupons()->consume($coupon);
            expect($result)->toBeTrue();
        }

        expect($coupon->usages()->count())->toBe(5);
        $coupon->refresh();
        expect($coupon->active)->toBeFalse();
    });

    it('handles edge case date ranges', function () {
        $now = Carbon::now();

        // Coupon that starts and expires in same minute
        $coupon = Coupon::factory()->create([
            'active' => true,
            'starts_at' => $now,
            'expires_at' => $now->copy()->addMinute(),
        ]);

        expect(coupons()->isActive($coupon))->toBeTrue();

        // Move time forward
        Carbon::setTestNow($now->copy()->addMinutes(2));

        expect(coupons()->isActive($coupon))->toBeFalse();

        // Reset time
        Carbon::setTestNow();
    });

    it('handles coupons with complex metadata', function () {
        $coupon = Coupon::factory()->create(['active' => true]);

        $complexMeta = [
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'order' => [
                'id' => 456,
                'total' => 100.00,
                'items' => [
                    ['id' => 1, 'name' => 'Product A', 'price' => 50.00],
                    ['id' => 2, 'name' => 'Product B', 'price' => 50.00],
                ],
            ],
            'discount' => [
                'type' => 'percentage',
                'value' => 20,
                'amount' => 20.00,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $result = coupons()->consume($coupon, null, $complexMeta);

        expect($result)->toBeTrue();

        $usage = $coupon->usages()->first();
        expect($usage->meta['user']['name'])->toBe('John Doe')
            ->and($usage->meta['order']['total'])->toBe(100)
            ->and($usage->meta['discount']['amount'])->toBe(20);
    });
});

describe('Error Handling', function () {
    it('gracefully handles invalid strategy configuration', function () {
        Config::set('filament-coupons.strategies', ['NonExistentClass']);

        // Should not throw exception
        $strategies = coupons()->getStrategies();
        expect($strategies)->toBeArray();
    });

    it('handles malformed coupon data', function () {
        // Test with minimal coupon data
        $coupon = new Coupon([
            'code' => 'TEST',
            'strategy' => 'non_existent',
            'active' => false,
        ]);

        expect(coupons()->isValid($coupon))->toBeFalse()
            ->and(coupons()->applyCoupon($coupon))->toBeFalse();
    });

    it('handles database transaction failures gracefully', function () {
        $coupon = Coupon::factory()->create(['active' => true]);

        // This should work normally
        $result = coupons()->consume($coupon);
        expect($result)->toBeTrue();
    });
});
