<?php

namespace App\Services;

use App\Models\TemuCollectedProduct;
use App\Models\Product1688Source;
use App\Services\ImageSearch\Platform1688Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TemuCollectionService
{
    protected $platform1688Service;

    public function __construct(Platform1688Service $platform1688Service)
    {
        $this->platform1688Service = $platform1688Service;
    }

    public function saveBatchProducts($userId, array $products, $siteUrl = null)
    {
        $savedProducts = [];

        foreach ($products as $productData) {
            // 提取价格并处理空字符串问题
            $rawPrice = $productData['sale_price'] ?? $productData['price'] ?? null;
            $salePrice = (is_numeric($rawPrice) && $rawPrice !== '') ? $rawPrice : null;

            $product = TemuCollectedProduct::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $productData['productId'] ?? $productData['product_id'] ?? null,
                ],
                [
                    'site_url' => $siteUrl ?? ($productData['site_url'] ?? null),
                    'platform' => 'temu',
                    'title' => $productData['title'] ?? $productData['productName'] ?? null,
                    'sale_price' => $salePrice,
                    'weight' => $productData['weight'] ?? null,
                    'brand' => $productData['brand'] ?? null,
                    // 修复：API返回的是 imageUrl，这里必须映射
                    'cover_image' => $productData['imageUrl'] ?? $productData['cover_image'] ?? $productData['coverUrl'] ?? null,
                    'product_data' => $productData,
                    'collected_at' => now(),
                ]
            );

            $savedProducts[] = $product;
        }

        return $savedProducts;
    }

    public function collectSimilarProducts($temuProductId, $userId, $searchMethod = 'image', $maxCount = 20)
    {
        $temuProduct = TemuCollectedProduct::where('id', $temuProductId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $existingCount = Product1688Source::where('temu_product_id', $temuProductId)->count();

        if ($existingCount >= $maxCount) {
            return [
                'success' => true,
                'message' => '已达到最大采集数量',
                'count' => $existingCount,
            ];
        }

        $remainingCount = $maxCount - $existingCount;

        try {
            Log::info('开始搜图，产品ID: ' . $temuProductId . '，图片URL: ' . $temuProduct->cover_image);

            // 1. 优先尝试使用 URL 搜图
            if (!empty($temuProduct->cover_image)) {
                $result = $this->platform1688Service->searchByUrl($temuProduct->cover_image, 1, $remainingCount);
            } else {
                return ['success' => false, 'message' => '缺少商品图片信息'];
            }

            if (!empty($result['data']) && is_array($result['data'])) {
                $saved = 0;
                $isFirstSource = ($existingCount === 0);

                foreach ($result['data'] as $item) {
                    if ($saved >= $remainingCount) {
                        break;
                    }

                    $source = Product1688Source::create([
                        'temu_product_id' => $temuProductId,
                        'user_id' => $userId,
                        'title' => $item['title'] ?? null,
                        'price' => $item['price'] ?? null,
                        'image' => $item['image'] ?? null,
                        'url' => $item['url'] ?? null,
                        'product_data' => $item,
                        'tags' => $item['tags'] ?? null,
                        'search_method' => $searchMethod,
                        'is_primary' => ($isFirstSource && $saved === 0), // 第一个货源设为主货源
                    ]);

                    $saved++;
                }

                return [
                    'success' => true,
                    'message' => "成功采集 {$saved} 条1688同款",
                    'count' => $saved,
                    'total' => $existingCount + $saved,
                ];
            }

            return [
                'success' => false,
                'message' => '未找到同款商品',
            ];

        } catch (\Exception $e) {
            Log::error('采集1688同款失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '采集失败: ' . $e->getMessage(),
            ];
        }
    }

    public function calculateProductProfit($temuProductId, $userId)
    {
        $temuProduct = TemuCollectedProduct::with(['primarySource', 'user'])
            ->where('id', $temuProductId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (!$temuProduct->primarySource) {
            return [
                'success' => false,
                'message' => '请先选择主货源',
            ];
        }

        $salePrice = (float) $temuProduct->sale_price;
        $purchasePrice = (float) $temuProduct->primarySource->price;
        $weight = (float) $temuProduct->weight;

        $user = $temuProduct->user;
        $freightPricePerKg = (float) $user->freight_price_per_kg;
        $operationFee = (float) $user->operation_fee;

        $freight = ($weight * $freightPricePerKg) + $operationFee;

        $platformFee = $salePrice * 0.05;

        $profit = $salePrice - $freight - $purchasePrice - $platformFee;

        return [
            'success' => true,
            'data' => [
                'sale_price' => $salePrice,
                'purchase_price' => $purchasePrice,
                'weight' => $weight,
                'freight' => round($freight, 2),
                'platform_fee' => round($platformFee, 2),
                'profit' => round($profit, 2),
                'freight_config' => [
                    'freight_price_per_kg' => $freightPricePerKg,
                    'operation_fee' => $operationFee,
                ],
            ],
        ];
    }

    public function setPrimarySource($sourceId, $temuProductId, $userId)
    {
        return DB::transaction(function () use ($sourceId, $temuProductId, $userId) {
            Product1688Source::where('temu_product_id', $temuProductId)
                ->where('user_id', $userId)
                ->update(['is_primary' => false]);

            $source = Product1688Source::where('id', $sourceId)
                ->where('temu_product_id', $temuProductId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $source->is_primary = true;
            $source->save();

            return $source;
        });
    }
}
