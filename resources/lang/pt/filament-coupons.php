<?php

declare(strict_types=1);

return [
    'action' => [
        'label' => 'Aplicar Cupom',
        'form' => [
            'code' => [
                'label' => 'Código do Cupom',
                'placeholder' => 'Digite seu código do cupom',
            ],
        ],
        'notifications' => [
            'success' => [
                'title' => 'Cupom Aplicado',
                'body' => 'Seu cupom foi aplicado com sucesso!',
            ],
            'invalid' => [
                'title' => 'Cupom Inválido',
                'body' => 'O código do cupom que você digitou é inválido ou expirou.',
            ],
            'error' => [
                'title' => 'Erro no Cupom',
                'body' => 'Ocorreu um erro ao aplicar o cupom. Por favor, tente novamente mais tarde.',
            ],
        ],
    ],

    'resource' => [
        'title' => 'Cupom',
        'plural_title' => 'Cupons',
        'usage' => 'Uso',
        'usages' => 'Usos',

        'form' => [
            'details' => 'Detalhes',
            'limits' => 'Limites',
            'multiple_creation' => [
                'heading' => 'Criação Múltipla',
                'description' => 'Crie vários cupons de uma vez especificando o número de cupons a serem gerados. Cada cupom terá um código único.',
            ],

            'fields' => [
                'code' => 'Código',
                'strategy' => 'Estratégia',
                'active' => 'Ativo',
                'starts_at' => [
                    'label' => 'Início',
                    'help' => 'Deixe vazio para sem data de início',
                ],
                'expires_at' => [
                    'label' => 'Expira em',
                    'help' => 'Deixe vazio para sem expiração',
                ],
                'usage_limit' => [
                    'label' => 'Limite de Uso',
                    'help' => 'Deixe vazio para uso ilimitado',
                ],
                'number_of_coupons' => 'Número de Cupons',
            ],
        ],

        'table' => [
            'columns' => [
                'code' => 'Código',
                'strategy' => 'Estratégia',
                'starts_at' => 'Início',
                'expires_at' => 'Expira em',
                'usage_limit' => 'Limite de Uso',
                'active' => 'Ativo',
                'created_at' => 'Criado em',
                'updated_at' => 'Atualizado em',
                'used_by' => 'Usado por',
                'used_at' => 'Usado em',
            ],
            'filters' => [
                'active' => 'Ativo',
                'inactive' => 'Inativo',
                'all' => 'Todos',
                'strategy' => 'Estratégia',
            ],
        ],
    ],
];
