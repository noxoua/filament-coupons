<?php

declare(strict_types=1);

use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

it('can create a strategy instance', function () {
    $strategy = new CouponStrategy();

    expect($strategy)->toBeInstanceOf(CouponStrategy::class);
});

it('generates correct name from class name', function () {
    $strategy = new CouponStrategy();

    expect($strategy->getName())->toBe('coupon');
});

it('generates correct label from name', function () {
    $strategy = new CouponStrategy();

    expect($strategy->getLabel())->toBe('Coupon');
});

it('has default apply method that returns true', function () {
    $strategy = new CouponStrategy();
    $coupon = new Coupon();

    expect($strategy->apply($coupon))->toBeTrue();
});

it('has default empty schema', function () {
    $strategy = new CouponStrategy();

    expect($strategy->schema())->toBe([]);
});

it('sets up default notifications', function () {
    $strategy = new CouponStrategy();

    // Access protected properties via reflection
    $reflection = new ReflectionClass($strategy);

    $successNotification = $reflection->getProperty('successNotification');
    $successNotification->setAccessible(true);

    $failureNotification = $reflection->getProperty('failureNotification');
    $failureNotification->setAccessible(true);

    expect($successNotification->getValue($strategy))->not()->toBeNull()
        ->and($failureNotification->getValue($strategy))->not()->toBeNull();
});

it('can customize name generation for different class names', function () {
    $customStrategy = new class extends CouponStrategy
    {
        // Class name will be something like class@anonymous...
        public function getName(): string
        {
            return 'custom_discount_strategy';
        }
    };

    expect($customStrategy->getName())->toBe('custom_discount_strategy');
});

it('can customize label generation', function () {
    $customStrategy = new class extends CouponStrategy
    {
        public function getName(): string
        {
            return 'free_shipping_strategy';
        }

        public function getLabel(): string
        {
            return 'Free Shipping Strategy';
        }
    };

    expect($customStrategy->getLabel())->toBe('Free Shipping Strategy');
});

it('can customize schema', function () {
    $customStrategy = new class extends CouponStrategy
    {
        public function schema(): array
        {
            return [
                'discount_amount' => 'required|numeric',
                'max_discount' => 'nullable|numeric',
            ];
        }
    };

    $schema = $customStrategy->schema();

    expect($schema)->toBe([
        'discount_amount' => 'required|numeric',
        'max_discount' => 'nullable|numeric',
    ]);
});

it('can customize apply method', function () {
    $customStrategy = new class extends CouponStrategy
    {
        public function apply(Coupon $coupon): bool
        {
            return $coupon->active;
        }
    };

    $activeCoupon = new Coupon(['active' => true]);
    $inactiveCoupon = new Coupon(['active' => false]);

    expect($customStrategy->apply($activeCoupon))->toBeTrue()
        ->and($customStrategy->apply($inactiveCoupon))->toBeFalse();
});
