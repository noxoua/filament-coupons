<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Noxo\FilamentCoupons\Models\Coupon;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => mb_strtoupper($this->faker->bothify('???###')),
            'strategy' => 'test_strategy',
            'payload' => null,
            'starts_at' => null,
            'expires_at' => null,
            'usage_limit' => null,
            'active' => true,
        ];
    }

    public function withPayload(array $payload): static
    {
        return $this->state(['payload' => $payload]);
    }

    public function withDates(?string $startsAt = null, ?string $expiresAt = null): static
    {
        return $this->state([
            'starts_at' => $startsAt ? $this->faker->dateTime($startsAt) : null,
            'expires_at' => $expiresAt ? $this->faker->dateTime($expiresAt) : null,
        ]);
    }

    public function withUsageLimit(int $limit): static
    {
        return $this->state(['usage_limit' => $limit]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function notStarted(): static
    {
        return $this->state([
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+1 year'),
        ]);
    }
}
