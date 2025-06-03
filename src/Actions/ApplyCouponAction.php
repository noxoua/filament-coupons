<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Noxo\FilamentCoupons\Models\Coupon;
use Throwable;

class ApplyCouponAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-coupons::filament-coupons.action.label'));
        $this->modalWidth(MaxWidth::Medium);

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
                    title: __('filament-coupons::filament-coupons.action.notifications.failure.title'),
                    body: __('filament-coupons::filament-coupons.action.notifications.failure.body')
                );

                return;
            }

            try {
                $strategy = coupons()->getStrategy($coupon->strategy);

                if (! $strategy) {
                    throw new Exception("Invalid coupon strategy: {$coupon->strategy}");
                }

                $applied = $strategy->apply($coupon);

                // Pass notification and redirect configurations to this action.
                $strategy->passToAction($this);

                $applied ? $this->success() : $this->failure();
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
