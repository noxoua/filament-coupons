<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Models\CouponUsage;

class CouponUsageFactory extends Factory
{
    protected $model = CouponUsage::class;

    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'couponable_type' => null,
            'couponable_id' => null,
            'meta' => null,
        ];
    }

    public function forCoupon(Coupon $coupon): static
    {
        return $this->state(['coupon_id' => $coupon->id]);
    }

    public function withMeta(array $meta): static
    {
        return $this->state(['meta' => $meta]);
    }

    public function forCouponable(string $type, int $id): static
    {
        return $this->state([
            'couponable_type' => $type,
            'couponable_id' => $id,
        ]);
    }
}
