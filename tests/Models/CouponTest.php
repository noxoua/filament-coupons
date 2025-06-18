<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Models\CouponUsage;

uses(RefreshDatabase::class);

it('can create a coupon', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'TEST123',
        'strategy' => 'test_strategy',
        'active' => true,
    ]);

    expect($coupon)->toBeInstanceOf(Coupon::class)
        ->and($coupon->code)->toBe('TEST123')
        ->and($coupon->strategy)->toBe('test_strategy')
        ->and($coupon->active)->toBeTrue();
});

it('has correct casts', function () {
    $coupon = Coupon::factory()->create([
        'payload' => ['key' => 'value'],
        'starts_at' => '2023-01-01 10:00:00',
        'expires_at' => '2023-12-31 23:59:59',
        'active' => true,
    ]);

    expect($coupon->payload)->toBeArray()
        ->and($coupon->payload)->toBe(['key' => 'value'])
        ->and($coupon->starts_at)->toBeInstanceOf(Carbon::class)
        ->and($coupon->expires_at)->toBeInstanceOf(Carbon::class)
        ->and($coupon->active)->toBeTrue();
});

it('has guarded fields empty', function () {
    $coupon = new Coupon();

    expect($coupon->getGuarded())->toBeEmpty();
});

it('has usages relationship', function () {
    $coupon = Coupon::factory()->create();

    expect($coupon->usages())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('can have multiple usages', function () {
    $coupon = Coupon::factory()->create();

    CouponUsage::factory()->count(3)->create(['coupon_id' => $coupon->id]);

    expect($coupon->usages)->toHaveCount(3);
});
