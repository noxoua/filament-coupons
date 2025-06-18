<?php

declare(strict_types=1);

use Noxo\FilamentCoupons\Coupons;

it('coupons helper function exists', function () {
    expect(function_exists('coupons'))->toBeTrue();
});

it('coupons helper returns coupons instance', function () {
    $instance = coupons();

    expect($instance)->toBeInstanceOf(Coupons::class);
});

it('coupons helper returns same instance', function () {
    $instance1 = coupons();
    $instance2 = coupons();

    expect($instance1)->toBe($instance2);
});
