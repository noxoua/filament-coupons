<?php

namespace Noxo\FilamentCoupons\Strategies;

use Filament\Forms\Form;
use Illuminate\Support\Str;
use Noxo\FilamentCoupons\Models\Coupon;

abstract class CouponStrategy
{
    /**
     * This name is utilized to store
     * and reference the strategy in the database.
     */
    public function getName(): string
    {
        return Str::of(static::class)
            ->afterLast('\\')
            ->snake()
            ->before('_strategy');
    }

    /**
     * Payload schema for the strategy.
     */
    public function schema(): array
    {
        return [
            // Define the schema for the strategy form.
            // This can be overridden in the concrete strategy classes.
        ];
    }

    abstract public function getLabel(): string;

    abstract public function apply(Coupon $coupon): bool;
}
