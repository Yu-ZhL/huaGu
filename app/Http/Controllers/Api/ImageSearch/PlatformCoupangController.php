<?php

namespace App\Http\Controllers\Api\ImageSearch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/** Coupang 平台搜同款控制器（预留） */
class PlatformCoupangController extends Controller
{
    public function searchByImage(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'code' => 501, 'data' => null, 'message' => '接口开发中'], 501);
    }

    public function searchByUrl(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'code' => 501, 'data' => null, 'message' => '接口开发中'], 501);
    }
}
