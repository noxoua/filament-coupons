<?php

declare(strict_types=1);

use Filament\Panel;
use Noxo\FilamentCoupons\CouponsPlugin;
use Noxo\FilamentCoupons\Resources\CouponResource;

it('can create plugin instance', function () {
    $plugin = CouponsPlugin::make();

    expect($plugin)->toBeInstanceOf(CouponsPlugin::class);
});

it('has correct plugin id', function () {
    $plugin = CouponsPlugin::make();

    expect($plugin->getId())->toBe('filament-coupons');
});

it('registers resources on panel', function () {
    $plugin = CouponsPlugin::make();

    // Create a mock panel to test registration
    $panel = new class extends Panel
    {
        public $registeredResources = [];

        public function resources(array $resources): static
        {
            $this->registeredResources = $resources;

            return $this;
        }

        public function getId(): string
        {
            return 'test';
        }
    };

    // Set up config for resources
    config(['filament-coupons.resources' => [CouponResource::class]]);

    $plugin->register($panel);

    expect($panel->registeredResources)->toContain(CouponResource::class);
});
it('boot method exists and can be called', function () {
    $plugin = CouponsPlugin::make();

    $panel = new class extends Panel
    {
        public function getId(): string
        {
            return 'test';
        }
    };

    // Should not throw any exceptions
    $plugin->boot($panel);

    expect(true)->toBeTrue();
});
