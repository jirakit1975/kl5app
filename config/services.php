<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    // เข้าสู่ระบบผ่าน RITTA Portal กลาง — secret อยู่ใน .env ฝั่ง server เท่านั้น (ห้ามตั้งเป็น VITE_*)
    'portal' => [
        'login_url'         => env('PORTAL_LOGIN_URL', 'http://portal.ritta.co.th/login?l=kl5'),
        'secret'            => env('PORTAL_LOGIN_HMAC_SECRET', ''),
        'allowed_domain'    => 'ritta.co.th',
        'ttl'               => 300, // วินาที
        'clock_skew'        => 30,  // ยอมให้นาฬิกา portal เร็วกว่าเครื่องเราได้ไม่เกิน 30 วินาที
        'auto_create_users' => (bool) env('PORTAL_AUTO_CREATE_USERS', false),
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

];
