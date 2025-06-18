<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Tests\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Models\CouponUsage;

uses(RefreshDatabase::class);

describe('Performance Tests', function () {
    it('can handle large number of coupons efficiently', function () {
        $startTime = microtime(true);

        // Create 1000 coupons
        Coupon::factory()->count(1000)->create(['active' => true]);

        $creationTime = microtime(true) - $startTime;

        // Should create 1000 coupons in reasonable time (less than 5 seconds)
        expect($creationTime)->toBeLessThan(5.0);

        // Verify count
        expect(Coupon::count())->toBe(1000);
    });

    it('can validate many coupons quickly', function () {
        // Create 100 coupons with various states
        $coupons = collect();

        $coupons = $coupons->merge(Coupon::factory()->count(25)->create(['active' => true]));
        $coupons = $coupons->merge(Coupon::factory()->count(25)->create(['active' => false]));
        $coupons = $coupons->merge(Coupon::factory()->count(25)->expired()->create());
        $coupons = $coupons->merge(Coupon::factory()->count(25)->notStarted()->create());

        $startTime = microtime(true);

        $validCoupons = 0;
        foreach ($coupons as $coupon) {
            if (coupons()->isValid($coupon)) {
                $validCoupons++;
            }
        }

        $validationTime = microtime(true) - $startTime;

        // Should validate 100 coupons in less than 1 second
        expect($validationTime)->toBeLessThan(1.0);

        // Should find exactly 25 valid coupons (only the active ones)
        expect($validCoupons)->toBe(25);
    });

    it('can handle bulk coupon consumption efficiently', function () {
        // Create 50 coupons
        $coupons = Coupon::factory()->count(50)->create(['active' => true]);

        $startTime = microtime(true);

        $consumedCount = 0;
        foreach ($coupons as $coupon) {
            if (coupons()->consume($coupon)) {
                $consumedCount++;
            }
        }

        $consumptionTime = microtime(true) - $startTime;

        // Should consume 50 coupons in less than 2 seconds
        expect($consumptionTime)->toBeLessThan(2.0);

        // All coupons should be consumed
        expect($consumedCount)->toBe(50);

        // Verify database state
        expect(CouponUsage::count())->toBe(50);
    });

    it('can handle coupons with usage limits efficiently', function () {
        // Create 10 coupons with usage limit of 100 each
        $coupons = Coupon::factory()->count(10)->create([
            'active' => true,
            'usage_limit' => 100,
        ]);

        $startTime = microtime(true);

        // Consume each coupon 50 times
        foreach ($coupons as $coupon) {
            for ($i = 0; $i < 50; $i++) {
                coupons()->consume($coupon);
            }
        }

        $consumptionTime = microtime(true) - $startTime;

        // Should handle 500 total consumptions in reasonable time
        expect($consumptionTime)->toBeLessThan(5.0);

        // Verify usage counts
        foreach ($coupons as $coupon) {
            expect($coupon->usages()->count())->toBe(50);
            expect(coupons()->canConsume($coupon))->toBeTrue(); // Still under limit
        }

        // Total usages should be 500
        expect(CouponUsage::count())->toBe(500);
    });

    it('can handle strategy operations efficiently', function () {
        // Create multiple strategies
        $strategies = [];
        for ($i = 0; $i < 10; $i++) {
            $strategies[] = new class extends \Noxo\FilamentCoupons\Strategies\CouponStrategy
            {
                private static $counter = 0;

                public function getName(): string
                {
                    return 'strategy_'.(++self::$counter);
                }
            };
        }

        config(['filament-coupons.strategies' => array_map('get_class', $strategies)]);

        $startTime = microtime(true);

        // Get all strategies multiple times
        for ($i = 0; $i < 100; $i++) {
            $allStrategies = coupons()->getStrategies();
            expect($allStrategies)->toHaveCount(10);
        }

        $strategyTime = microtime(true) - $startTime;

        // Should handle 100 strategy retrievals quickly
        expect($strategyTime)->toBeLessThan(1.0);
    });

    it('maintains performance with complex payload schemas', function () {
        $complexStrategy = new class extends \Noxo\FilamentCoupons\Strategies\CouponStrategy
        {
            public function getName(): string
            {
                return 'complex_strategy';
            }

            public function schema(): array
            {
                $schema = [];

                // Create a complex schema with many fields
                for ($i = 0; $i < 50; $i++) {
                    $schema["field_$i"] = [
                        'type' => 'string',
                        'required' => $i % 2 === 0,
                        'validation' => ['min:1', 'max:100'],
                        'options' => range(1, 20),
                    ];
                }

                return $schema;
            }
        };

        config(['filament-coupons.strategies' => [get_class($complexStrategy)]]);

        $startTime = microtime(true);

        // Get schema multiple times
        for ($i = 0; $i < 50; $i++) {
            $schema = coupons()->getStrategyPayloadSchema('complex_strategy');
            expect($schema)->toHaveCount(50);
        }

        $schemaTime = microtime(true) - $startTime;

        // Should handle complex schemas efficiently
        expect($schemaTime)->toBeLessThan(1.0);
    });
});

describe('Memory Usage Tests', function () {
    it('does not leak memory with many coupon operations', function () {
        $initialMemory = memory_get_usage();

        // Perform many operations
        for ($i = 0; $i < 100; $i++) {
            $coupon = Coupon::factory()->create(['active' => true]);
            coupons()->isValid($coupon);
            coupons()->consume($coupon);
        }

        // Force garbage collection
        gc_collect_cycles();

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable (less than 50MB)
        expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024);
    });

    it('handles large payload data efficiently', function () {
        $initialMemory = memory_get_usage();

        // Create coupons with large payloads
        $largePayload = [];
        for ($i = 0; $i < 1000; $i++) {
            $largePayload["key_$i"] = str_repeat('data', 100); // 400 bytes per key
        }

        $coupons = Coupon::factory()->count(10)->create([
            'active' => true,
            'payload' => $largePayload,
        ]);

        // Consume all coupons
        foreach ($coupons as $coupon) {
            coupons()->consume($coupon);
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Should handle large payloads without excessive memory usage
        expect($memoryIncrease)->toBeLessThan(100 * 1024 * 1024);
    });
});
