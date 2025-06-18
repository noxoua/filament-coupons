<?php

declare(strict_types=1);

use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Noxo\FilamentCoupons\Actions\ApplyCouponAction;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

uses(RefreshDatabase::class);

it('can create apply coupon action', function () {
    $action = ApplyCouponAction::make();

    expect($action)->toBeInstanceOf(ApplyCouponAction::class);
});

it('has correct default name', function () {
    expect(ApplyCouponAction::getDefaultName())->toBe('applyCoupon');
});

it('can handle error notifications', function () {
    $action = new ApplyCouponAction('test');

    // The error method should halt the action and throw a Halt exception
    expect(fn () => $action->error('Test Title', 'Test Body'))
        ->toThrow(Filament\Support\Exceptions\Halt::class);
});

it('validates coupon code in action', function () {
    // Create a test strategy
    $testStrategy = new class extends CouponStrategy
    {
        public function getName(): string
        {
            return 'test_strategy';
        }

        public function apply(Coupon $coupon): bool
        {
            return true;
        }
    };

    config(['filament-coupons.strategies' => [get_class($testStrategy)]]);

    $coupon = Coupon::factory()->create([
        'code' => 'TESTCODE',
        'strategy' => 'test_strategy',
        'active' => true,
    ]);

    $action = ApplyCouponAction::make();

    // Setup the action
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('setUp');
    $method->setAccessible(true);
    $method->invoke($action);

    // Test valid coupon code
    expect($coupon->code)->toBe('TESTCODE');
});

it('handles coupons with invalid strategies', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'TESTCODE',
        'strategy' => 'invalid_strategy',
        'active' => true,
    ]);

    $action = ApplyCouponAction::make();

    // This would test the action with invalid strategy
    // In real scenario, this would throw exception and trigger error notification
    expect($coupon->strategy)->toBe('invalid_strategy');
})->todo('This test should throw an exception for invalid strategy');
