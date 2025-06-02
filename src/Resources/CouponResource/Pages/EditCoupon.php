<?php

namespace Noxo\FilamentCoupons\Resources\CouponResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Noxo\FilamentCoupons\Resources\CouponResource;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Details';
    }
}
