<?php

namespace Noxo\FilamentCoupons;

use Illuminate\Filesystem\Filesystem;
use Noxo\FilamentCoupons\Commands\CreateStrategyCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CouponsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-coupons';

    public static string $viewNamespace = 'filament-coupons';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('noxoua/filament-coupons');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }
    }

    public function packageRegistered(): void
    {
        //
    }

    public function packageBooted(): void
    {
        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__.'/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-coupons/{$file->getFilename()}"),
                ], 'filament-coupons-stubs');
            }
        }

        $this->app->singleton(static::$name, fn ($app) => new \Noxo\FilamentCoupons\Coupons);
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            CreateStrategyCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            '2025_06_01_205833_create_coupons_table',
            '2025_06_01_205834_create_coupon_usages_table',
        ];
    }
}
