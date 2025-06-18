<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Noxo\FilamentCoupons\Concerns\CanNotifyAndRedirect;

it('can set success redirect url', function () {
    $mock = new class
    {
        use CanNotifyAndRedirect;
    };

    $result = $mock->successRedirectUrl('/success');

    expect($result)->toBe($mock);

    // Use reflection to check protected property
    $reflection = new ReflectionClass($mock);
    $property = $reflection->getProperty('successRedirectUrl');
    $property->setAccessible(true);

    expect($property->getValue($mock))->toBe('/success');
});

it('can set failure redirect url', function () {
    $mock = new class
    {
        use CanNotifyAndRedirect;
    };

    $result = $mock->failureRedirectUrl('/failure');

    expect($result)->toBe($mock);

    // Use reflection to check protected property
    $reflection = new ReflectionClass($mock);
    $property = $reflection->getProperty('failureRedirectUrl');
    $property->setAccessible(true);

    expect($property->getValue($mock))->toBe('/failure');
});

it('can set success notification', function () {
    $mock = new class
    {
        use CanNotifyAndRedirect;
    };

    $notification = Notification::make()->title('Success');
    $result = $mock->successNotification($notification);

    expect($result)->toBe($mock);

    // Use reflection to check protected property
    $reflection = new ReflectionClass($mock);
    $property = $reflection->getProperty('successNotification');
    $property->setAccessible(true);

    expect($property->getValue($mock))->toBe($notification);
});

it('can set failure notification', function () {
    $mock = new class
    {
        use CanNotifyAndRedirect;
    };

    $notification = Notification::make()->title('Failure');
    $result = $mock->failureNotification($notification);

    expect($result)->toBe($mock);

    // Use reflection to check protected property
    $reflection = new ReflectionClass($mock);
    $property = $reflection->getProperty('failureNotification');
    $property->setAccessible(true);

    expect($property->getValue($mock))->toBe($notification);
});

it('can set redirect url with closure', function () {
    $mock = new class
    {
        use CanNotifyAndRedirect;
    };

    $closure = fn () => '/dynamic-url';
    $result = $mock->successRedirectUrl($closure);

    expect($result)->toBe($mock);

    // Use reflection to check protected property
    $reflection = new ReflectionClass($mock);
    $property = $reflection->getProperty('successRedirectUrl');
    $property->setAccessible(true);

    expect($property->getValue($mock))->toBe($closure);
});

it('can set notification with closure', function () {
    $mock = new class
    {
        use CanNotifyAndRedirect;
    };

    $closure = fn (Notification $notification) => $notification->title('Dynamic');
    $result = $mock->successNotification($closure);

    expect($result)->toBe($mock);

    // Use reflection to check protected property
    $reflection = new ReflectionClass($mock);
    $property = $reflection->getProperty('successNotification');
    $property->setAccessible(true);

    expect($property->getValue($mock))->toBe($closure);
});

it('can pass configuration to action', function () {
    $mock = new class
    {
        use CanNotifyAndRedirect;

        public function __construct()
        {
            $this->successRedirectUrl('/success');
            $this->failureRedirectUrl('/failure');
            $this->successNotification(Notification::make()->title('Success'));
            $this->failureNotification(Notification::make()->title('Failure'));
        }
    };

    // Create a simple test action that tracks method calls
    $action = new class('test') extends Action
    {
        public $successRedirectUrlCalled = false;

        public $failureRedirectUrlCalled = false;

        public $successNotificationCalled = false;

        public $failureNotificationCalled = false;

        public function successRedirectUrl($url): static
        {
            $this->successRedirectUrlCalled = true;

            return $this;
        }

        public function failureRedirectUrl($url): static
        {
            $this->failureRedirectUrlCalled = true;

            return $this;
        }

        public function successNotification($notification): static
        {
            $this->successNotificationCalled = true;

            return $this;
        }

        public function failureNotification($notification): static
        {
            $this->failureNotificationCalled = true;

            return $this;
        }
    };

    $mock->passToAction($action);

    expect($action->successRedirectUrlCalled)->toBeTrue()
        ->and($action->failureRedirectUrlCalled)->toBeTrue()
        ->and($action->successNotificationCalled)->toBeTrue()
        ->and($action->failureNotificationCalled)->toBeTrue();
});
