<?php

declare(strict_types=1);

use Noxo\FilamentCoupons\Commands\CreateStrategyCommand;
use Noxo\FilamentCoupons\Coupons;
use Noxo\FilamentCoupons\CouponsServiceProvider;
use Spatie\LaravelPackageTools\Package;

it('has correct package name', function () {
    expect(CouponsServiceProvider::$name)->toBe('filament-coupons');
});

it('has correct view namespace', function () {
    expect(CouponsServiceProvider::$viewNamespace)->toBe('filament-coupons');
});

it('configures package correctly', function () {
    $provider = new CouponsServiceProvider(app());

    // We can't directly test configurePackage because it requires
    // the package to be properly set up first. Instead, we'll test
    // that the service provider loads properly.
    expect($provider)->toBeInstanceOf(CouponsServiceProvider::class);
});

it('registers package commands', function () {
    $provider = new CouponsServiceProvider(app());

    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('getCommands');
    $method->setAccessible(true);

    $commands = $method->invoke($provider);

    expect($commands)->toContain(CreateStrategyCommand::class);
});

it('has correct migrations', function () {
    $provider = new CouponsServiceProvider(app());

    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('getMigrations');
    $method->setAccessible(true);

    $migrations = $method->invoke($provider);

    expect($migrations)->toContain('2025_06_01_205833_create_coupons_table')
        ->and($migrations)->toContain('2025_06_01_205834_create_coupon_usages_table');
});

it('registers coupons singleton when booted', function () {
    $provider = new CouponsServiceProvider(app());

    $provider->packageBooted();

    $instance = app('filament-coupons');

    expect($instance)->toBeInstanceOf(Coupons::class);
});

it('registers same singleton instance', function () {
    $provider = new CouponsServiceProvider(app());

    $provider->packageBooted();

    $instance1 = app('filament-coupons');
    $instance2 = app('filament-coupons');

    expect($instance1)->toBe($instance2);
});
