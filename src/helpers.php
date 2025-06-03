<?php

declare(strict_types=1);

if (! function_exists('coupons')) {
    function coupons()
    {
        return app('filament-coupons');
    }
}
