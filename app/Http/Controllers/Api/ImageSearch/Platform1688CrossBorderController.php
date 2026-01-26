<?php

namespace App\Http\Controllers\Api\ImageSearch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 1688 跨境平台搜同款控制器
 * 
 * 预留接口，待后续实现
 * 
 * @group 图片搜索：1688跨境
 */
class Platform1688CrossBorderController extends Controller
{
    /**
     * 以图搜图（待实现）
     */
    public function searchByImage(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 501,
            'data' => null,
            'message' => '接口开发中',
        ], 501);
    }

    /**
     * URL 搜图（待实现）
     */
    public function searchByUrl(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 501,
            'data' => null,
            'message' => '接口开发中',
        ], 501);
    }
}
