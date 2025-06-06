<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Resources\CouponResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Noxo\FilamentCoupons\Resources\CouponResource;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $number = max(1, (int) ($data['number_of_coupons'] ?? 1));
        $generateCode = $number > 1;
        unset($data['number_of_coupons']);

        for ($i = 0; $i < $number; $i++) {
            $data['code'] = $generateCode ? Str::random(10) : $data['code'];
            $record = parent::handleRecordCreation($data);
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
