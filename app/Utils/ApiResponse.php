<?php

namespace App\Utils;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    public static function error(string $message = '操作失败', int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ], $code);
    }
}
