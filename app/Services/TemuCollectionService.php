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
        $savedIds = [];

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
                    // API返回的是 imageUrl，这里必须映射
                    'cover_image' => $productData['imageUrl'] ?? $productData['cover_image'] ?? $productData['coverUrl'] ?? null,
                    'product_data' => $productData,
                    'remark' => $productData['remark'] ?? null,
                    'sales' => $productData['sales'] ?? 0,
                    'reviews' => $productData['commonNum'] ?? $productData['reviews'] ?? 0,
                    'rating' => $productData['score'] ?? $productData['rating'] ?? 0,
                    'freight' => $productData['freightCharges'] ?? 0,
                    'profit' => $productData['forecastProfits'] ?? 0,
                    'source_price_1688' => $productData['sourcePrice'] ?? 0,
                    'is_brand' => $productData['isBrand'] ?? false,
                    'shop_name' => $productData['shopName'] ?? null,
                    'collected_at' => now(),
                ]
            );
            $savedIds[] = $product->id;
        }

        if (!empty($savedIds)) {
            return TemuCollectedProduct::whereIn('id', $savedIds)
                ->withCount('sources1688')
                ->with([
                    'sources1688' => function ($query) {
                        $query->orderBy('is_primary', 'desc');
                    }
                ])
                ->get()
                ->map(function ($product) {
                    $firstSource = $product->sources1688->first();
                    // 这里不需要 setAttribute, 只是临时用
                    $product->first_source_image = $firstSource ? $firstSource->image : null;
                    $product->first_source_price = $firstSource ? $firstSource->price : null;
                    return $product;
                });
        }

        return [];
    }

    public function collectSimilarProducts($temuProductId, $userId, $searchMethod = 'image', $maxCount = 20, $forcedImgUrl = null)
    {
        $temuProduct = TemuCollectedProduct::where('user_id', $userId)
            ->where(function ($q) use ($temuProductId) {
                $q->where('id', $temuProductId)
                    ->orWhere('product_id', $temuProductId);
            })
            ->first();

        if (!$temuProduct) {
            Log::info("采集同款时动态补全商品记录: {$temuProductId}");
            $temuProduct = TemuCollectedProduct::updateOrCreate(
                ['user_id' => $userId, 'product_id' => $temuProductId],
                [
                    'platform' => 'temu',
                    'cover_image' => $forcedImgUrl,
                    'collected_at' => now(),
                ]
            );
        }

        // 使用查到的真实数据库ID
        $dbId = $temuProduct->id;
        $temuProductId = $dbId; // 修正为数据库ID，用于后续查询 sources

        $existingCount = Product1688Source::where('temu_product_id', $temuProductId)->count();

        if ($existingCount >= $maxCount) {
            return [
                'success' => true,
                'message' => '已达到最大采集数量',
                'product_id' => $temuProductId, // 返回 DB ID
                'count' => $existingCount,
            ];
        }

        $remainingCount = $maxCount - $existingCount;

        try {
            // 优先尝试使用 URL 搜图
            // 优先使用前端传来的 forcedImgUrl，其次使用数据库里的 cover_image
            $targetImgUrl = $forcedImgUrl ?? $temuProduct->cover_image;

            if (!empty($targetImgUrl)) {
                if (strpos($targetImgUrl, 'data:image') === 0) {
                    // 提取纯净的 Base64 内容 (去掉 data:image/xxx;base64, 前缀)
                    $parts = explode(',', $targetImgUrl);
                    $base64Data = count($parts) > 1 ? $parts[1] : $parts[0];

                    // 彻底移除可能存在的换行符或空格
                    $base64Data = str_replace(["\r", "\n", " "], '', $base64Data);

                    $result = $this->platform1688Service->searchByImage($base64Data, 1, $remainingCount);
                } else {
                    $result = $this->platform1688Service->searchByUrl($targetImgUrl, 1, $remainingCount);
                }
            } else {
                return ['success' => false, 'message' => '缺少商品图片信息'];
            }

            // 如果URL搜图没结果，尝试转Base64搜图 (用户明确要求)
            $hasData = !empty($result['data']) && is_array($result['data']) && count($result['data']) > 0;

            if (!$hasData && !empty($temuProduct->cover_image)) {
                try {
                    // 使用 file_get_contents 下载图片，设置超时
                    $ctx = stream_context_create(['http' => ['timeout' => 15]]); // 15秒超时
                    $imageContent = @file_get_contents($temuProduct->cover_image, false, $ctx);

                    if ($imageContent) {
                        $base64 = base64_encode($imageContent);
                        // 调用 Platform1688Service 的 Base64 搜图方法
                        $result = $this->platform1688Service->searchByImage($base64, 1, $remainingCount);
                    } else {
                        // Log::warning('图片下载失败，无法执行Base64搜图');
                    }
                } catch (\Exception $e) {
                    // Log::error('Base64搜图异常: ' . $e->getMessage());
                }
            }

            if (!empty($result['data']) && is_array($result['data'])) {
                // Log::info('搜图成功，结果数量: ' . count($result['data']));

                // [DEBUG] 打印第一条数据结构，以便排查字段名问题
                if (isset($result['data'][0])) {
                }

                $saved = 0;
                $isFirstSource = ($existingCount === 0);

                foreach ($result['data'] as $item) {
                    if ($saved >= $remainingCount) {
                        break;
                    }

                    // 构建标签
                    $tags = [];
                    if (!empty($item['shippingTimeGuarantee'])) {
                        $tags[] = $item['shippingTimeGuarantee'];
                    }
                    if (!empty($item['repurchaseRate'])) {
                        $tags[] = '复购' . $item['repurchaseRate'];
                    }
                    if (!empty($item['monthSold'])) {
                        $tags[] = '月销' . $item['monthSold'];
                    }

                    $source = Product1688Source::create([
                        'temu_product_id' => $temuProductId,
                        'user_id' => $userId,
                        'title' => $item['title'] ?? $item['subject'] ?? null,
                        'image' => $item['picture'] ?? $item['ori_picture'] ?? $item['image'] ?? $item['imageUrl'] ?? $item['imgUrl'] ?? null,
                        'price' => $item['price'] ?? null,
                        'url' => $item['real_url'] ?? $item['url'] ?? $item['detailUrl'] ?? null,
                        'product_data' => $item,
                        'tags' => !empty($tags) ? $tags : null,
                        'search_method' => $searchMethod,
                        'is_primary' => ($isFirstSource && $saved === 0), // 第一个货源设为主货源
                    ]);

                    $saved++;
                }

                return [
                    'success' => true,
                    'message' => "成功采集 {$saved} 条1688同款",
                    'product_id' => $temuProductId, // 返回 DB ID
                    'count' => $saved,
                    'total' => $existingCount + $saved,
                ];
            } else {
                // Log::warning('搜图未找到结果，API返回: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            }

            return [
                'success' => false,
                'message' => '未找到同款商品',
            ];

        } catch (\Exception $e) {
            // Log::error('采集1688同款失败: ' . $e->getMessage());
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
