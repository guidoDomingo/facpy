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

    'sifen' => [
        'test_url' => env('SIFEN_TEST_URL', 'https://sifen-test.set.gov.py/de/ws/'),
        'prod_url' => env('SIFEN_PROD_URL', 'https://sifen.set.gov.py/de/ws/'),
        'default_env' => env('SIFEN_DEFAULT_ENV', 'test'),
        'timeout' => env('SIFEN_TIMEOUT', 30),
        'max_retry' => env('SIFEN_MAX_RETRY', 3),
        'batch_size' => env('SIFEN_BATCH_SIZE', 15),
    ],

];
