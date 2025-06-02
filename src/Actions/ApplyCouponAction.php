<?php

namespace Noxo\FilamentCoupons\Actions;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Noxo\FilamentCoupons\Exceptions\CouponException;
use Noxo\FilamentCoupons\Models\Coupon;
use Throwable;

class ApplyCouponAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'applyCoupon';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Apply Coupon');
        $this->modalWidth(MaxWidth::Medium);
        $this->successNotification(
            Notification::make()
                ->title('Coupon Applied')
                ->body('Your coupon has been successfully applied!')
                ->success()
        );

        $this->form(fn () => [
            Forms\Components\TextInput::make('code')
                ->label('Coupon Code')
                ->placeholder('Enter your coupon code')
                ->required()
                ->maxLength(20),
        ]);

        $this->action(function (array $data): void {
            $coupon = Coupon::query()
                ->where('code', $data['code'])
                ->first();

            if (! $coupon || ! coupons()->isValid($coupon)) {
                $this->error(
                    title: 'Invalid Coupon',
                    body: 'The coupon code you entered is either invalid or has expired.'
                );
                $this->halt();

                return;
            }

            try {
                coupons()->applyCoupon($coupon);

                $this->success();
            } catch (CouponException $e) {
                $this->error(
                    title: 'Coupon Error',
                    body: $e->getMessage()
                );
            } catch (Throwable $e) {
                report($e);
                $this->error(
                    title: 'An error occurred',
                    body: 'Something went wrong while applying the coupon. Please try again later.'
                );
            }
        });
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
