<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $code
 * @property-read string $strategy
 * @property-read array|null $payload
 * @property-read \Carbon\Carbon|null $starts_at
 * @property-read \Carbon\Carbon|null $expires_at
 * @property-read int|null $usage_limit
 * @property-read bool $active
 */
class Coupon extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    protected static function booted()
    {
        static::deleting(function (Coupon $coupon) {
            $coupon->usages()->delete();
        });
    }
}
