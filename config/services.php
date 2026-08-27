<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'qontak' => [
        'base_url' => env('QONTAK_BASE_URL', 'https://api.mekari.com/qontak/chat'),
        'client_id' => env('QONTAK_CLIENT_ID'),
        'client_secret' => env('QONTAK_CLIENT_SECRET'),
        'channel_integration_id' => env('QONTAK_CHANNEL_INTEGRATION_ID'),
        'template_id' => env('QONTAK_TEMPLATE_ID'),
        'enabled' => env('QONTAK_ENABLED', true),
        'oauth_url' => env('QONTAK_OAUTH_URL', 'https://api.mekari.com/oauth/token'),
    ],

    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
    ],

];
