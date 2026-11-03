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

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-3.1-flash-lite'),
        // Google Search grounding cần gói có hạn mức tương ứng; hết hạn mức sẽ tự chuyển sang chế độ không tra web.
        'web_search' => (bool) env('GEMINI_WEB_SEARCH', true),
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'minimal'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 45),
    ],

    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'expire_minutes' => (int) env('VNPAY_EXPIRE_MINUTES', 15),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'checkout' => [
        // Giao tận nơi chỉ áp dụng cho phụ kiện nhỏ lẻ có tổng tiền hàng không vượt mức này.
        'delivery_max_subtotal' => (int) env('DELIVERY_MAX_SUBTOTAL', 5000000),
    ],

];
