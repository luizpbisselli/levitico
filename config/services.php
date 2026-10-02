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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Caixa de e-mail dedicada que recebe os XMLs de NF-e/CT-e (README seção 2)
    'ingestao_email' => [
        'host'              => env('INGESTAO_EMAIL_HOST'),
        'port'              => env('INGESTAO_EMAIL_PORT', 993),
        'user'              => env('INGESTAO_EMAIL_USER'),
        'password'          => env('INGESTAO_EMAIL_PASSWORD'),
        'box'               => env('INGESTAO_EMAIL_BOX', 'INBOX'),
        'pasta_processados' => env('INGESTAO_EMAIL_PASTA', 'Processados'),
    ],

];
