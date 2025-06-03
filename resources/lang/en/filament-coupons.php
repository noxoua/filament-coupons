<?php

declare(strict_types=1);

return [
    'action' => [
        'label' => 'Apply Coupon',
        'form' => [
            'code' => [
                'label' => 'Coupon Code',
                'placeholder' => 'Enter your coupon code',
            ],
        ],
        'notifications' => [
            'success' => [
                'title' => 'Coupon Applied',
                'body' => 'Your coupon has been successfully applied!',
            ],
            'failure' => [
                'title' => 'Invalid Coupon',
                'body' => 'The coupon code you entered is either invalid or has expired.',
            ],
            'error' => [
                'title' => 'Coupon Error',
                'body' => 'An error occurred while applying the coupon. Please try again later.',
            ],
        ],
    ],

    'resource' => [
        'title' => 'Coupon',
        'plural_title' => 'Coupons',
        'usage' => 'Usage',
        'usages' => 'Usages',

        'form' => [
            'details' => 'Details',
            'limits' => 'Limits',
            'multiple_creation' => [
                'heading' => 'Multiple Creation',
                'description' => 'Create multiple coupons at once by specifying the number of coupons to generate. Each coupon will have a unique code.',
            ],

            'fields' => [
                'code' => 'Code',
                'strategy' => 'Strategy',
                'active' => 'Active',
                'starts_at' => [
                    'label' => 'Starts At',
                    'help' => 'Leave empty for no start date',
                ],
                'expires_at' => [
                    'label' => 'Expires At',
                    'help' => 'Leave empty for no expiration',
                ],
                'usage_limit' => [
                    'label' => 'Usage Limit',
                    'help' => 'Leave empty for unlimited usage',
                ],
                'number_of_coupons' => 'Number of Coupons',
            ],
        ],

        'table' => [
            'columns' => [
                'code' => 'Code',
                'strategy' => 'Strategy',
                'starts_at' => 'Starts At',
                'expires_at' => 'Expires At',
                'usage_limit' => 'Usage Limit',
                'active' => 'Active',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
                'used_by' => 'Used By',
                'used_at' => 'Used At',
            ],
            'filters' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'all' => 'All',
                'strategy' => 'Strategy',
            ],
        ],
    ],
];
