<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class CouponsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

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
}
