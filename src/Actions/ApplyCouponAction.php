<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Actions;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Noxo\FilamentCoupons\Exceptions\CouponException;
use Noxo\FilamentCoupons\Models\Coupon;
use Throwable;

final class ApplyCouponAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-coupons::filament-coupons.action.label'));
        $this->modalWidth(MaxWidth::Medium);
        $this->successNotification(
            Notification::make()
                ->title(__('filament-coupons::filament-coupons.action.notifications.success.title'))
                ->body(__('filament-coupons::filament-coupons.action.notifications.success.body'))
                ->success()
        );

        $this->form(fn () => [
            Forms\Components\TextInput::make('code')
                ->label(__('filament-coupons::filament-coupons.action.form.code.label'))
                ->placeholder(__('filament-coupons::filament-coupons.action.form.code.placeholder'))
                ->required()
                ->maxLength(20),
        ]);

        $this->action(function (array $data): void {
            $coupon = Coupon::query()
                ->where('code', $data['code'])
                ->first();

            if (! $coupon || ! coupons()->isValid($coupon)) {
                $this->error(
                    title: __('filament-coupons::filament-coupons.action.notifications.invalid.title'),
                    body: __('filament-coupons::filament-coupons.action.notifications.invalid.body')
                );
                $this->halt();

                return;
            }

            try {
                coupons()->applyCoupon($coupon);

                $this->success();
            } catch (CouponException $e) {
                $this->error(
                    title: __('filament-coupons::filament-coupons.action.notifications.error.title'),
                    body: $e->getMessage()
                );
            } catch (Throwable $e) {
                report($e);
                $this->error(
                    title: __('filament-coupons::filament-coupons.action.notifications.error.title'),
                    body: __('filament-coupons::filament-coupons.action.notifications.error.body')
                );
            }
        });
    }

    public static function getDefaultName(): string
    {
        return 'applyCoupon';
    }

    public function error(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->send();
        $this->halt();
    }
}
