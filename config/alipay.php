<?php

return [
    'gateway_url' => env('ALIPAY_GATEWAY_URL', 'https://openapi.alipay.com/gateway.do'),

    'app_id' => env('ALIPAY_APP_ID', ''),

    'private_key' => env('ALIPAY_PRIVATE_KEY', ''),

    'public_key' => env('ALIPAY_PUBLIC_KEY', ''),

    'notify_url' => env('ALIPAY_NOTIFY_URL', env('APP_URL') . '/api/payment/alipay-notify'),
];
