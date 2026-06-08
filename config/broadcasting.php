<?php

return [
    'default' => env('BROADCAST_DRIVER', 'pusher'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS' => true,
                'host' => env('PUSHER_HOST'),
                'port' => env('PUSHER_PORT', 443),
                'scheme' => 'https'
            ],
        ],

        'websockets' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY', 'app-key'),
            'secret' => env('PUSHER_APP_SECRET', 'app-secret'),
            'app_id' => env('PUSHER_APP_ID', 'app-id'),
            'options' => [
                'host' => env('WEBSOCKETS_HOST', 'localhost'),
                'port' => env('WEBSOCKETS_PORT', 6001),
                'scheme' => 'http',
                'encrypted' => false,
            ],
            'client_options' => [
                'scheme' => 'http',
                'host' => env('WEBSOCKETS_HOST', 'localhost'),
                'port' => env('WEBSOCKETS_PORT', 6001),
                'encrypted' => false,
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];
