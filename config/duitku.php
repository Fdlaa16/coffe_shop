<?php

return [
    'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),
    'api_key' => env('DUITKU_API_KEY', ''),
    'base_url' => env('DUITKU_BASE_URL', 'https://sandbox.duitku.com'), // sandbox or https://passport.duitku.com for production
    'callback_url' => env('DUITKU_CALLBACK_URL', 'http://localhost:8000/api/duitku/callback'),
    'return_url' => env('DUITKU_RETURN_URL', 'http://localhost:8000/payment/success'),
];
