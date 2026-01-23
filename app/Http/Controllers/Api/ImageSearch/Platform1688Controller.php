<?php

namespace App\Http\Controllers\Api\ImageSearch;

use App\Http\Controllers\Controller;
use App\Services\ImageSearch\Platform1688Service;
use App\Utils\ImageSearch\ImageUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 1688 平台搜同款控制器
 * 
 * @group 1688搜同款 (1688)
 */
class Platform1688Controller extends Controller
{
    private $service;

    public function __construct(Platform1688Service $service)
    {
        $this->service = $service;
    }

    /**
     * 1688 以图搜图
     * 
     * 上传图片文件或提供 Base64 编码的图片数据，在 1688 平台搜索相似商品
     *
     * @authenticated
     *
     * @param  Request  $request
     * @return JsonResponse
     * 
     * @bodyParam image file 图片文件（与 image_base64 二选一）
     * @bodyParam image_base64 string 图片的 Base64 编码（与 image 二选一） Example: /9j/4AAQSkZJRg...
     * @bodyParam page integer 页码，默认 1 Example: 1
     * @bodyParam size integer 每页数量，默认 20 Example: 20
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": [
     *     {
     *       "title": "2024新款现货懒人神器",
     *       "price": "29.90",
     *       "image": "https://cbu01.alicdn.com/img/ibank/xxx.jpg",
     *       "url": "https://detail.1688.com/offer/12345.html"
     *     }
     *   ],
     *   "message": "搜图成功，找到 20 条结果"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "code": 400,
     *   "data": null,
     *   "message": "请提供图片文件或 Base64 数据"
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function searchByImage(Request $request): JsonResponse
    {
        try {
            $imageBase64 = null;

            // 优先处理上传的文件
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                if (!$file->isValid()) {
                    return response()->json([
                        'success' => false,
                        'code' => 400,
                        'data' => null,
                        'message' => '图片文件无效',
                    ], 400);
                }

                $imageBase64 = ImageUtil::fileToBase64($file);

            } elseif ($request->has('image_base64')) {
                $imageBase64 = $request->input('image_base64');

                // 校验 Base64 数据
                if (!ImageUtil::validateBase64($imageBase64)) {
                    return response()->json([
                        'success' => false,
                        'code' => 400,
                        'data' => null,
                        'message' => 'Base64 图片数据格式不正确',
                    ], 400);
                }

                // 去掉可能的 data URI 前缀
                if (strpos($imageBase64, 'data:image') === 0) {
                    $imageBase64 = substr($imageBase64, strpos($imageBase64, ',') + 1);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'code' => 400,
                    'data' => null,
                    'message' => '请提供图片文件或 Base64 数据',
                ], 400);
            }

            $page = $request->input('page', 1);
            $size = $request->input('size', 20);

            $result = $this->service->searchByImage($imageBase64, $page, $size);

            return response()->json($result, $result['code']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'code' => 500,
                'data' => null,
                'message' => '服务器错误: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 1688 URL 搜图
     * 
     * 提供图片 URL 地址，在 1688 平台搜索相似商品
     *
     * @authenticated
     *
     * @param  Request  $request
     * @return JsonResponse
     * 
     * @bodyParam url string required 图片 URL 地址 Example: https://img.kwcdn.com/product/fancy/605bead6-9775-4ddf-a732-09f369e697b5.jpg
     * @bodyParam page integer 页码，默认 1 Example: 1
     * @bodyParam size integer 每页数量，默认 20 Example: 20
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": [
     *     {
     *       "title": "2024新款现货懒人神器",
     *       "price": "29.90",
     *       "image": "https://cbu01.alicdn.com/img/ibank/xxx.jpg",
     *       "url": "https://detail.1688.com/offer/12345.html"
     *     }
     *   ],
     *   "message": "搜图成功，找到 20 条结果"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "code": 400,
     *   "data": null,
     *   "message": "请提供图片 URL"
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function searchByUrl(Request $request): JsonResponse
    {
        try {
            $url = $request->input('url');

            if (empty($url)) {
                return response()->json([
                    'success' => false,
                    'code' => 400,
                    'data' => null,
                    'message' => '请提供图片 URL',
                ], 400);
            }

            $page = $request->input('page', 1);
            $size = $request->input('size', 20);

            $result = $this->service->searchByUrl($url, $page, $size);

            return response()->json($result, $result['code']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'code' => 500,
                'data' => null,
                'message' => '服务器错误: ' . $e->getMessage(),
            ], 500);
        }
    }
}
