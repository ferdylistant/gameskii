<?php
    return [
        'defaults' => [
            'guard' => env('AUTH_GUARD', 'user'),
            'passwords' => 'users'
        ],

        'guards' => [
            'user' => [
                'driver' => 'passport',
                'provider' => 'users',
            ],
            'api' => [
                'driver' => 'api'
            ],
        ],

        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                'model' => \App\Models\User::class
            ],
        ],

        'passwords' => [
            'users' => [
                'provider' => 'users',
                'email' => 'auth.emails.password',
                'table' => 'password_resets',
                'expire' => 60,
                'throttle' => 60,
            ],
        ],
        'password_timeout' => 10800,
    ];
