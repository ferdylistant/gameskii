<?php
return [

/*
 * This package comes with multi tenancy out of the box. Here you can
 * configure the different apps that can use the webSockets server.
 *
 * Optionally you can disable client events so clients cannot send
 * messages to each other via the webSockets.
 */
'apps' => [
    [
        'id' => env('PUSHER_APP_ID'),
        'name' => env('APP_NAME'),
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'enable_client_messages' => false,
        'enable_statistics' => true,
    ],
],

/*
 * This class is responsible for finding the apps. The default provider
 * will use the apps defined in this config file.
 *
 * You can create a custom provider by implementing the
 * `AppProvider` interface.
 */
'providers' => \BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider::class,

/*
 * This array contains the hosts of which you want to allow incoming requests.
 * Leave this empty if you want to accept requests from all hosts.
 */
'allowed_origins' => [
    //
],

/*
 * The maximum request size in kilobytes that is allowed for an incoming WebSocket request.
 */
'max_request_size_in_kb' => 250,

/*
 * This path will be used to register the necessary routes for the package.
 */
'path' => 'laravel-websockets',

'statistics' => [
    /*
     * This model will be used to store the statistics of the WebSocketsServer.
     * The only requirement is that the model should extend
     * `WebSocketsStatisticsEntry` provided by this package.
     */
    'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,

    /*
     * Here you can specify the interval in seconds at which statistics should be logged.
     */
    'interval_in_seconds' => 60,

    /*
     * When the clean-command is executed, all recorded statistics older than
     * the number of days specified here will be deleted.
     */
    'delete_statistics_older_than_days' => 60,

    /*
     * Use an DNS resolver to make the requests to the statistics logger
     * default is to resolve everything to 127.0.0.1.
     */
    'perform_dns_lookup' => false,
],

'ssl' => [
    'local_cert' => null,

    'local_pk' => null,

    'passphrase' => null
],
];
