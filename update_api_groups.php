<?php

$dir = __DIR__ . '/app/Http/Controllers/Api/ImageSearch';
$files = glob($dir . '/*.php');

$platformNames = [
    'Platform1688Controller.php' => '1688',
    'Platform1688CrossBorderController.php' => '1688跨境',
    'Platform1688ProController.php' => '1688专业版',
    'PlatformTaobaoController.php' => '淘宝',
    'PlatformTaobaoLiteController.php' => '淘宝特价版',
    'PlatformAlibabaController.php' => '阿里巴巴国际',
    'PlatformAmazonController.php' => '亚马逊',
    'PlatformAmazonLiteController.php' => '亚马逊Lite',
    'PlatformAliExpressController.php' => '速卖通',
    'PlatformAliExpressProController.php' => '速卖通专业版',
    'PlatformSheinController.php' => 'SHEIN',
    'PlatformOzonController.php' => 'Ozon',
    'PlatformNaverShoppingController.php' => 'Naver Shopping',
    'PlatformEbayController.php' => 'eBay',
    'PlatformGoogleLensController.php' => 'Google Lens',
    'PlatformBingController.php' => '必应',
    'PlatformBaiduController.php' => '百度',
    'PlatformYandexController.php' => 'Yandex',
    'PlatformWildberriesController.php' => 'Wildberries',
    'PlatformDomeggookController.php' => 'Domeggook',
    'PlatformCoupangController.php' => 'Coupang',
    'PlatformNetseaController.php' => 'Netsea',
    'PlatformOwnerclanController.php' => 'Ownerclan',
    'PlatformOnch3Controller.php' => 'Onch3',
    'PlatformMercariController.php' => 'Mercari',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $basename = basename($file);

    if (isset($platformNames[$basename])) {
        $name = $platformNames[$basename];
        $newGroup = "@group 图片搜索：{$name}";

        // 替换 @group 行
        $content = preg_replace('/@group\s+.*(\r\n|\n)/', "$newGroup$1", $content);

        file_put_contents($file, $content);
        echo "Updated $basename to group: $newGroup\n";
    }
}

echo "All done!\n";
