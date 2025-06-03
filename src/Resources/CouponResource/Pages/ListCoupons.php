<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Resources\CouponResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Noxo\FilamentCoupons\Resources\CouponResource;

final class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
