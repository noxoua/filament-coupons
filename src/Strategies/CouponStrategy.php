<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Strategies;

use Filament\Notifications\Notification;
use Noxo\FilamentCoupons\Concerns\CanNotifyAndRedirect;
use Noxo\FilamentCoupons\Models\Coupon;

class CouponStrategy
{
    use CanNotifyAndRedirect;

    public function __construct()
    {
        $this->setUp();
    }

    public function setUp(): void
    {
        $this->successNotification(
            fn (Notification $notification) => $notification
                ->title(__('filament-coupons::filament-coupons.action.notifications.success.title'))
                ->body(__('filament-coupons::filament-coupons.action.notifications.success.body'))
        );

        $this->failureNotification(
            fn (Notification $notification) => $notification
                ->title(__('filament-coupons::filament-coupons.action.notifications.failure.title'))
                ->body(__('filament-coupons::filament-coupons.action.notifications.failure.body'))
        );
    }

    public function getLabel(): string
    {
        return (string) str($this->getName())
            ->replace('_', ' ')
            ->title();
    }

    public function apply(Coupon $coupon): bool
    {
        return true;
    }

    /**
     * This name is utilized to store
     * and reference the strategy in the database.
     */
    public function getName(): string
    {
        return (string) str(static::class)
            ->afterLast('\\')
            ->snake()
            ->before('_strategy');
    }

    /**
     * Payload schema for the strategy.
     */
    public function schema(): array
    {
        return [
            // Define the schema for the strategy form.
            // This can be overridden in the concrete strategy classes.
        ];
    }
}
