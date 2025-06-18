<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Models\CouponUsage;

uses(RefreshDatabase::class);

it('can create a coupon usage', function () {
    $coupon = Coupon::factory()->create();

    $usage = CouponUsage::factory()->create([
        'coupon_id' => $coupon->id,
        'meta' => ['key' => 'value'],
    ]);

    expect($usage)->toBeInstanceOf(CouponUsage::class)
        ->and($usage->coupon_id)->toBe($coupon->id)
        ->and($usage->meta)->toBe(['key' => 'value']);
});

it('has correct casts', function () {
    $coupon = Coupon::factory()->create();

    $usage = CouponUsage::factory()->create([
        'coupon_id' => $coupon->id,
        'meta' => ['test' => 'data'],
    ]);

    expect($usage->meta)->toBeArray()
        ->and($usage->meta)->toBe(['test' => 'data']);
});

it('has guarded fields empty', function () {
    $usage = new CouponUsage();

    expect($usage->getGuarded())->toBeEmpty();
});

it('belongs to coupon', function () {
    $coupon = Coupon::factory()->create();
    $usage = CouponUsage::factory()->create(['coupon_id' => $coupon->id]);

    expect($usage->coupon())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($usage->coupon->id)->toBe($coupon->id);
});

it('has couponable morph relationship', function () {
    $usage = new CouponUsage();

    expect($usage->couponable())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

it('has user alias for couponable relationship', function () {
    $usage = new CouponUsage();

    expect($usage->user())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphTo::class);

    // Test that user() returns the same as couponable()
    expect($usage->user()->getMorphType())->toBe($usage->couponable()->getMorphType())
        ->and($usage->user()->getForeignKeyName())->toBe($usage->couponable()->getForeignKeyName());
});
