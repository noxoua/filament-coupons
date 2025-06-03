<?php

return [
    'action' => [
        'label' => 'Gutschein einlösen',
        'form' => [
            'code' => [
                'label' => 'Gutscheincode',
                'placeholder' => 'Geben Sie Ihren Gutscheincode ein',
            ],
        ],
        'notifications' => [
            'success' => [
                'title' => 'Gutschein angewendet',
                'body' => 'Ihr Gutschein wurde erfolgreich angewendet!',
            ],
            'invalid' => [
                'title' => 'Ungültiger Gutschein',
                'body' => 'Der von Ihnen eingegebene Gutscheincode ist entweder ungültig oder abgelaufen.',
            ],
            'error' => [
                'title' => 'Gutscheinfehler',
                'body' => 'Beim Anwenden des Gutscheins ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            ],
        ],
    ],

    'resource' => [
        'title' => 'Gutschein',
        'plural_title' => 'Gutscheine',
        'usage' => 'Verwendung',
        'usages' => 'Verwendungen',

        'form' => [
            'details' => 'Details',
            'limits' => 'Limits',
            'multiple_creation' => [
                'heading' => 'Mehrfacherstellung',
                'description' => 'Erstellen Sie mehrere Gutscheine gleichzeitig, indem Sie die Anzahl der zu generierenden Gutscheine angeben. Jeder Gutschein erhält einen eindeutigen Code.',
            ],

            'fields' => [
                'code' => 'Code',
                'strategy' => 'Strategie',
                'active' => 'Aktiv',
                'starts_at' => [
                    'label' => 'Startet am',
                    'help' => 'Leer lassen für kein Startdatum',
                ],
                'expires_at' => [
                    'label' => 'Läuft ab am',
                    'help' => 'Leer lassen für kein Ablaufdatum',
                ],
                'usage_limit' => [
                    'label' => 'Nutzungsbegrenzung',
                    'help' => 'Leer lassen für unbegrenzte Nutzung',
                ],
                'number_of_coupons' => 'Anzahl der Gutscheine',
            ],
        ],

        'table' => [
            'columns' => [
                'code' => 'Code',
                'strategy' => 'Strategie',
                'starts_at' => 'Startet am',
                'expires_at' => 'Läuft ab am',
                'usage_limit' => 'Nutzungsbegrenzung',
                'active' => 'Aktiv',
                'created_at' => 'Erstellt am',
                'updated_at' => 'Aktualisiert am',
                'used_by' => 'Verwendet von',
                'used_at' => 'Verwendet am',
            ],
            'filters' => [
                'active' => 'Aktiv',
                'inactive' => 'Inaktiv',
                'all' => 'Alle',
                'strategy' => 'Strategie',
            ],
        ],
    ],
];
