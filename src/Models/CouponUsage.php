<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read int $id
 * @property-read int $coupon_id
 * @property-read string $couponable_type
 * @property-read int $couponable_id
 * @property-read array|null $meta
 * @property-read Coupon $coupon
 */
class CouponUsage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Alias for `couponable` relation.
     */
    public function user(): MorphTo
    {
        return $this->couponable();
    }

    public function couponable(): MorphTo
    {
        return $this->morphTo();
    }
}
