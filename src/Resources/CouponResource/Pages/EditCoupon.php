<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Resources\CouponResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Noxo\FilamentCoupons\Resources\CouponResource;

final class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return __('filament-coupons::filament-coupons.resource.form.details');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
