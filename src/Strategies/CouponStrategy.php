<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Strategies;

use Noxo\FilamentCoupons\Models\Coupon;

class CouponStrategy
{
    public function getLabel(): string
    {
        return (string) str($this->getName())
            ->replace('_', ' ')
            ->title();
    }

    public function apply(Coupon $coupon): bool
    {
        return true;
    }

    /**
     * This name is utilized to store
     * and reference the strategy in the database.
     */
    public function getName(): string
    {
        return (string) str(static::class)
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
}
