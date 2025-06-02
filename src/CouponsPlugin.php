<?php

namespace Noxo\FilamentCoupons;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CouponsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-coupons';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources(config('filament-coupons.resources'));
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
