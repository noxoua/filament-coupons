<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Resources\CouponResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Noxo\FilamentCoupons\Resources\CouponResource;

final class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $number = $data['number_of_coupons'];
        $generateCode = $number > 1;
        unset($data['number_of_coupons']);

        for ($i = 0; $i < $number; $i++) {
            $data['code'] = $generateCode ? Str::random(10) : $data['code'];
            $record = parent::handleRecordCreation($data);
        }

        return $record;
    }
}
