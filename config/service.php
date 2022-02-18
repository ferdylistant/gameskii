<?php
    return [
        'passport' => [
            'user' => [
                'login_endpoint'    => env('PASSPORT_LOGIN_ENDPOINT_USER'),
                'client_id'    => env('PASSPORT_CLIENT_ID_USER'),
                'client_secret'    => env('PASSPORT_CLIENT_SECRET_USER')
            ],

        ],

        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_URL_CALLBACK'),
        ],

        // 'mailgun' => [
        //     'domain' => env('MAILGUN_DOMAIN'),
        //     'secret' => env('MAILGUN_SECRET'),
        // ],

        // 'mandrill' => [
        //     'secret' => env('MANDRILL_SECRET'),
        // ],

        // 'ses' => [
        //     'key'    => env('SES_KEY'),
        //     'secret' => env('SES_SECRET'),
        //     'region' => 'us-east-1',
        // ],

        // 'stripe' => [
        //     'model'  => App\User::class,
        //     'key'    => env('STRIPE_KEY'),
        //     'secret' => env('STRIPE_SECRET'),
        // ],
    ];
