<?php

namespace Noxo\FilamentCoupons;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

class Coupons
{
    /**
     * Get the payload schema for a specific coupon strategy.
     *
     * @return array<int, mixed>
     */
    public function getStrategyPayloadSchema(?string $strategyName = null): array
    {
        $couponStrategy = $this->getStrategies()[$strategyName] ?? null;

        if (! $couponStrategy) {
            return [];
        }

        return $couponStrategy->schema();
    }

    /**
     * Check if a specific coupon strategy has a payload schema.
     */
    public function hasStrategyPayloadSchema(?string $strategyName = null): bool
    {
        return filled($this->getStrategyPayloadSchema($strategyName));
    }

    /**
     * Get all available coupon strategies.
     *
     * @return array<string, CouponStrategy>
     */
    public function getStrategies(): array
    {
        $strategies = config('filament-coupons.strategies', []);

        return collect($strategies)
            ->mapWithKeys(function ($strategyClass) {
                $strategy = new $strategyClass;

                return [$strategy->getName() => $strategy];
            })
            ->all();
    }

    /**
     * Apply a coupon using its strategy.
     */
    public function applyCoupon(Coupon $coupon): bool
    {
        $couponStrategy = $this->getStrategies()[$coupon->strategy] ?? null;

        if (! $couponStrategy) {
            return false;
        }

        return $couponStrategy->apply($coupon);
    }

    /**
     * Check if a coupon is valid.
     *
     * A coupon is valid if it is active and can be consumed.
     */
    public function isValid(Coupon $coupon): bool
    {
        return $this->isActive($coupon) && $this->canConsume($coupon);
    }

    /**
     * Check if a coupon is active.
     *
     * A coupon is active if:
     * - It is marked as active
     * - The current date is greater than or equal to the start date (if set)
     * - The current date is less than or equal to the expiration date (if set)
     */
    public function isActive(Coupon $coupon): bool
    {
        $now = now();

        $startsAtValid = $coupon->starts_at === null || $now->gte($coupon->starts_at);
        $expiresAtValid = $coupon->expires_at === null || $now->lte($coupon->expires_at);

        return $coupon->active && $startsAtValid && $expiresAtValid;
    }

    /**
     * Check if a coupon can be consumed.
     *
     * A coupon can be consumed if:
     * - It has no usage limit, or
     * - The usage limit is greater than the current number of usages.
     */
    public function canConsume(Coupon $coupon): bool
    {
        return $coupon->usage_limit === null || $coupon->usage_limit > $coupon->usages()->count();
    }

    /**
     * Consume a coupon.
     *
     * This method creates a new usage record for the coupon and optionally deactivates the coupon
     * if the usage limit is reached.
     */
    public function consume(
        Coupon $coupon,
        ?Model $couponable = null,
        array $meta = [],
        bool $deactiveIfLimitReached = true
    ): bool {
        if (! $this->isValid($coupon)) {
            return false;
        }

        return DB::transaction(function () use ($coupon, $couponable, $meta, $deactiveIfLimitReached) {
            // Create new usage instance without persisting
            $usage = $coupon->usages()->make(['meta' => $meta]);

            // Associate couponable if provided
            if ($couponable) {
                $usage->couponable()->associate($couponable);
            }

            // Persist usage
            $usage->save();

            // If the usage limit is reached, deactivate the coupon.
            if (! $this->canConsume($coupon) && $deactiveIfLimitReached) {
                $coupon->update(['active' => false]);
            }

            return true;
        });
    }
}
