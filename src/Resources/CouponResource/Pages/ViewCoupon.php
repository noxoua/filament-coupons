<?php

namespace Noxo\FilamentCoupons\Resources\CouponResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Noxo\FilamentCoupons\Resources\CouponResource;

class ViewCoupon extends ViewRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return __('filament-coupons::filament-coupons.resource.form.details');
    }
}
