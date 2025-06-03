<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

trait CanNotifyAndRedirect
{
    protected string|Closure|null $failureRedirectUrl = null;

    protected string|Closure|null $successRedirectUrl = null;

    protected Notification|Closure|null $failureNotification = null;

    protected Notification|Closure|null $successNotification = null;

    public function failureRedirectUrl(string|Closure|null $url): static
    {
        $this->failureRedirectUrl = $url;

        return $this;
    }

    public function successRedirectUrl(string|Closure|null $url): static
    {
        $this->successRedirectUrl = $url;

        return $this;
    }

    public function failureNotification(Notification|Closure|null $notification): static
    {
        $this->failureNotification = $notification;

        return $this;
    }

    public function successNotification(Notification|Closure|null $notification): static
    {
        $this->successNotification = $notification;

        return $this;
    }

    public function passToAction(Action $action): void
    {
        // Set redirect
        $action->successRedirectUrl($this->successRedirectUrl);
        $action->failureRedirectUrl($this->failureRedirectUrl);

        // Set notifications
        $action->successNotification($this->successNotification);
        $action->failureNotification($this->failureNotification);
    }
}
