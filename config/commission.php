<?php

return [
    'csv_format' => [
        'date_column' => 0,
        'user_id_column' => 1,
        'user_type_column' => 2,
        'operation_type_column' => 3,
        'amount_column' => 4,
        'currency_column' => 5,
    ],
    'user_type' => [
        'private',
        'business',
    ],
    'operation_type' => [
        'deposit',
        'withdraw',
    ],
    'currency_type' => [
        'EUR',
        'USD',
        'JPY',
    ],
    'commission_rate' => [
        'deposit' => [
            'private' => 0.03,
            'business' => 0.03
        ],
        'withdraw' => [
            'private' => 0.3,
            'business' => 0.5,
            'weekly_discount' => [
                'base_currency' => 'EUR',
                'maximum_free_withdraw' => 3,
                'maximum_free_amount' => 1000
            ],
        ],
    ],
    'input_date_format' => 'Y-m-d',
    'currency_exchange' => [
        'access_key' => env('EXCHANGE_RATE_API_ACCESS_KEY'),
    ],
    'japanese_yen' => 'JPY'
];
