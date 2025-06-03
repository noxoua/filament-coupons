<?php

declare(strict_types=1);

return [
    'resources' => [
        Noxo\FilamentCoupons\Resources\CouponResource::class,
    ],

    'navigation' => [
        'icon' => 'heroicon-o-ticket',
        'active_icon' => 'heroicon-s-ticket',
        'sort' => 60,
        'group' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Coupon Strategies
    |--------------------------------------------------------------------------
    |
    | This is the list of coupon strategies that will be used by the package.
    | You can use this command `php artisan make:coupons-strategy` to create a new strategy.
    |
    */
    'strategies' => [
        // \App\Coupons\FreeSubscriptionStrategy::class,
    ],

    /*
    |----------------------------------------------------------------------
    | Couponable Model
    |----------------------------------------------------------------------
    |
    | This is the coluum name that will be used in the `usages` relation manager
    | to display the model that used the coupon.
    |
    */
    'couponable_column' => 'name',
];
