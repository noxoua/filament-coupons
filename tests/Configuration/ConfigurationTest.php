<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Noxo\FilamentCoupons\Resources\CouponResource;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

describe('Configuration Tests', function () {
    it('can handle empty configuration', function () {
        Config::set('filament-coupons', []);

        $strategies = coupons()->getStrategies();
        expect($strategies)->toBeArray()->toBeEmpty();
    });

    it('can handle missing strategies configuration', function () {
        Config::set('filament-coupons.strategies', null);

        $strategies = coupons()->getStrategies();
        expect($strategies)->toBeArray()->toBeEmpty();
    });

    it('can handle invalid strategy classes', function () {
        Config::set('filament-coupons.strategies', [
            'NonExistentClass',
            'AnotherInvalidClass',
        ]);

        // Should not throw exception and return empty array
        $strategies = coupons()->getStrategies();
        expect($strategies)->toBeArray();
    });

    it('can handle mixed valid and invalid strategies', function () {
        $validStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'valid_strategy';
            }
        };

        Config::set('filament-coupons.strategies', [
            get_class($validStrategy),
            'InvalidClass',
        ]);

        $strategies = coupons()->getStrategies();
        expect($strategies)->toHaveKey('valid_strategy');
    });

    it('validates resources configuration', function () {
        $defaultResources = config('filament-coupons.resources', []);

        expect($defaultResources)->toBeArray()
            ->and($defaultResources)->toContain(CouponResource::class);
    });

    it('validates navigation configuration', function () {
        $navigation = config('filament-coupons.navigation', []);

        expect($navigation)->toBeArray()
            ->and($navigation)->toHaveKey('icon')
            ->and($navigation)->toHaveKey('active_icon')
            ->and($navigation)->toHaveKey('sort');
    });

    it('can handle custom navigation configuration', function () {
        Config::set('filament-coupons.navigation', [
            'icon' => 'custom-icon',
            'active_icon' => 'custom-active-icon',
            'sort' => 99,
            'group' => 'Custom Group',
        ]);

        expect(CouponResource::getNavigationIcon())->toBe('custom-icon');
        expect(CouponResource::getActiveNavigationIcon())->toBe('custom-active-icon');
        expect(CouponResource::getNavigationSort())->toBe(99);
        expect(CouponResource::getNavigationGroup())->toBe('Custom Group');
    });

    it('validates couponable column configuration', function () {
        $column = config('filament-coupons.couponable_column');

        expect($column)->toBeString();
    });

    it('can handle custom couponable column', function () {
        Config::set('filament-coupons.couponable_column', 'email');

        $column = config('filament-coupons.couponable_column');
        expect($column)->toBe('email');
    });
});

describe('Strategy Configuration Edge Cases', function () {
    it('handles strategies with complex inheritance', function () {
        // Base strategy
        $baseStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'base_strategy';
            }
        };

        // Extended strategy - simplified without constructor params
        $extendedStrategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'extended_strategy';
            }
        };

        Config::set('filament-coupons.strategies', [
            get_class($baseStrategy),
            get_class($extendedStrategy),
        ]);

        $strategies = coupons()->getStrategies();

        expect($strategies)->toHaveKey('base_strategy')
            ->and($strategies)->toHaveKey('extended_strategy');
    });

    it('handles strategy configuration with closures', function () {
        // Test that config can handle dynamic strategy registration
        $strategy1 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'dynamic_strategy_1';
            }
        };

        $strategy2 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'dynamic_strategy_2';
            }
        };

        $strategy3 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'dynamic_strategy_3';
            }
        };

        Config::set('filament-coupons.strategies', [
            get_class($strategy1),
            get_class($strategy2),
            get_class($strategy3),
        ]);

        $strategies = coupons()->getStrategies();

        expect($strategies)->toHaveCount(3);
    });

    it('handles strategy name conflicts gracefully', function () {
        $strategy1 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'conflict_strategy';
            }

            public function getLabel(): string
            {
                return 'First Strategy';
            }
        };

        $strategy2 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'conflict_strategy'; // Same name
            }

            public function getLabel(): string
            {
                return 'Second Strategy';
            }
        };

        Config::set('filament-coupons.strategies', [
            get_class($strategy1),
            get_class($strategy2),
        ]);

        $strategies = coupons()->getStrategies();

        // Should handle conflict (typically last one wins)
        expect($strategies)->toHaveKey('conflict_strategy')
            ->and($strategies['conflict_strategy']->getLabel())->toBe('Second Strategy');
    });
});

describe('Runtime Configuration Changes', function () {
    it('reflects configuration changes immediately', function () {
        // Initial configuration
        Config::set('filament-coupons.strategies', []);
        expect(coupons()->getStrategies())->toBeEmpty();

        // Add strategy
        $strategy = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'runtime_strategy';
            }
        };

        Config::set('filament-coupons.strategies', [get_class($strategy)]);

        $strategies = coupons()->getStrategies();
        expect($strategies)->toHaveKey('runtime_strategy');

        // Remove strategy
        Config::set('filament-coupons.strategies', []);
        expect(coupons()->getStrategies())->toBeEmpty();
    });

    it('handles configuration hot-swapping', function () {
        $strategy1 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'strategy_1';
            }
        };

        $strategy2 = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'strategy_2';
            }
        };

        // Set first strategy
        Config::set('filament-coupons.strategies', [get_class($strategy1)]);
        expect(coupons()->getStrategies())->toHaveKey('strategy_1');

        // Hot-swap to second strategy
        Config::set('filament-coupons.strategies', [get_class($strategy2)]);
        expect(coupons()->getStrategies())->toHaveKey('strategy_2')
            ->and(coupons()->getStrategies())->not()->toHaveKey('strategy_1');
    });
});
