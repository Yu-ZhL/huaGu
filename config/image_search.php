<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 1688 平台配置
    |--------------------------------------------------------------------------
    */
    '1688' => [
        'base_url' => env('IMAGE_SEARCH_1688_URL', 'https://api.aliprice.com/index.php/chrome/items/imageAnalysis'),
        'sign' => env('IMAGE_SEARCH_1688_SIGN', ''),
        'headers' => [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'browser' => 'chrome',
            'channel' => 'chrome',
            'content-type' => 'application/json;charset=UTF-8',
            'cookie' => env('IMAGE_SEARCH_1688_COOKIE', ''),
            'dnt' => '1',
            'ext-id' => '10354',
            'origin' => 'chrome-extension://dbjldigbjceebginhcnmjnigbocicokh',
            'platform' => '1688',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'version' => '3.6.8',
        ],
        'default_params' => [
            'page' => '1',
            'size' => '20',
            'website' => '1688_lite2',
            'language' => 'zh-CN',
            'currency' => 'USD',
            'domain' => 'detail.1688.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 其他平台配置（预留）
    |--------------------------------------------------------------------------
    */
    '1688_cross_border' => [],
    '1688_pro' => [],
    'taobao' => [],
    'taobao_lite' => [],
    'alibaba' => [],
    'amazon' => [],
    'amazon_lite' => [],
    'aliexpress' => [],
    'aliexpress_pro' => [],
    'shein' => [],
    'ozon' => [],
    'naver_shopping' => [],
    'ebay' => [],
    'google_lens' => [],
    'bing' => [],
    'baidu' => [],
    'yandex' => [],
    'wildberries' => [],
    'domeggook' => [],
    'coupang' => [],
    'netsea' => [],
    'ownerclan' => [],
    'onch3' => [],
    'mercari' => [],
];
