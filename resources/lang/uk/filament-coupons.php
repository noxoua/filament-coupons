<?php

declare(strict_types=1);

return [
    'action' => [
        'label' => 'Використати купон',
        'form' => [
            'code' => [
                'label' => 'Код купону',
                'placeholder' => 'Введіть код купону',
            ],
        ],
        'notifications' => [
            'success' => [
                'title' => 'Купон застосовано',
                'body' => 'Ваш купон успішно застосовано!',
            ],
            'failure' => [
                'title' => 'Невірний купон',
                'body' => 'Введений код купону недійсний або прострочений.',
            ],
            'error' => [
                'title' => 'Помилка купону',
                'body' => 'Під час застосування купону сталася помилка. Будь ласка, спробуйте пізніше.',
            ],
        ],
    ],

    'resource' => [
        'title' => 'Купон',
        'plural_title' => 'Купони',
        'usage' => 'Використання',
        'usages' => 'Використання',

        'form' => [
            'details' => 'Деталі',
            'limits' => 'Обмеження',
            'multiple_creation' => [
                'heading' => 'Множинне створення',
                'description' => 'Створіть кілька купонів одночасно, вказавши кількість купонів для генерації. Кожен купон матиме унікальний код.',
            ],

            'fields' => [
                'code' => 'Код',
                'strategy' => 'Стратегія',
                'active' => 'Активно',
                'starts_at' => [
                    'label' => 'Починається',
                    'help' => 'Залиште порожнім для відсутності дати початку',
                ],
                'expires_at' => [
                    'label' => 'Закінчується',
                    'help' => 'Залиште порожнім для відсутності терміну придатності',
                ],
                'usage_limit' => [
                    'label' => 'Ліміт використання',
                    'help' => 'Залиште порожнім для необмеженого використання',
                ],
                'number_of_coupons' => 'Кількість купонів',
            ],
        ],

        'table' => [
            'columns' => [
                'code' => 'Код',
                'strategy' => 'Стратегія',
                'starts_at' => 'Дата початку',
                'expires_at' => 'Дата закінчення',
                'usage_limit' => 'Ліміт використання',
                'active' => 'Активно',
                'created_at' => 'Створено',
                'updated_at' => 'Оновлено',
                'used_by' => 'Використано користувачем',
                'used_at' => 'Дата використання',
            ],
            'filters' => [
                'active' => 'Активні',
                'inactive' => 'Неактивні',
                'all' => 'Всі',
                'strategy' => 'Стратегія',
            ],
        ],
    ],
];
