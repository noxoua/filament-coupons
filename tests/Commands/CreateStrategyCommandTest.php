<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Noxo\FilamentCoupons\Commands\CreateStrategyCommand;

beforeEach(function () {
    // Clean up any test files
    $this->testPath = app_path('Coupons/TestStrategy.php');
    if (File::exists($this->testPath)) {
        File::delete($this->testPath);
    }

    // Ensure directory exists
    if (! File::exists(app_path('Coupons'))) {
        File::makeDirectory(app_path('Coupons'), 0755, true);
    }
});

afterEach(function () {
    // Clean up test files
    if (File::exists($this->testPath)) {
        File::delete($this->testPath);
    }
});

it('has correct command signature', function () {
    $command = new CreateStrategyCommand();

    $reflection = new ReflectionClass($command);
    $property = $reflection->getProperty('signature');
    $property->setAccessible(true);

    expect($property->getValue($command))->toBe('make:coupons-strategy {name?}');
});

it('has correct description', function () {
    $command = new CreateStrategyCommand();

    expect($command->description)->toBe('Create a new coupon strategy');
});

it('can handle strategy name with strategy suffix', function () {
    $this->artisan('make:coupons-strategy', ['name' => 'TestStrategy'])
        ->assertExitCode(0);

    expect(File::exists($this->testPath))->toBeTrue();
});

it('can handle strategy name without strategy suffix', function () {
    $this->artisan('make:coupons-strategy', ['name' => 'Test'])
        ->assertExitCode(0);

    expect(File::exists($this->testPath))->toBeTrue();
});

it('publishes config if it does not exist', function () {
    $configPath = config_path('filament-coupons.php');

    // Remove config if it exists
    if (File::exists($configPath)) {
        File::delete($configPath);
    }

    $this->artisan('make:coupons-strategy', ['name' => 'Test'])
        ->assertExitCode(0);

    expect(File::exists($configPath))->toBeTrue();
});

it('uses CanManipulateFiles trait', function () {
    $command = new CreateStrategyCommand();

    $traits = class_uses($command);

    expect($traits)->toContain('Filament\Support\Commands\Concerns\CanManipulateFiles');
});
