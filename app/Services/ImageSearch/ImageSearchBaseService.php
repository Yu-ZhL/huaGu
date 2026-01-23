<?php

namespace App\Services\ImageSearch;

/**
 * 搜同款服务基类
 */
abstract class ImageSearchBaseService
{
    /**
     * 成功响应
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return array
     */
    protected function successResponse($data = [], $message = '请求成功', $code = 200)
    {
        return [
            'success' => true,
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ];
    }

    /**
     * 错误响应
     *
     * @param  string  $message
     * @param  int  $code
     * @param  mixed  $data
     * @return array
     */
    protected function errorResponse($message = '请求失败', $code = 500, $data = null)
    {
        return [
            'success' => false,
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ];
    }

    /**
     * 验证图片参数（预留扩展）
     *
     * @param  mixed  $image
     * @return bool
     */
    protected function validateImage($image)
    {
        // 预留验证逻辑
        return true;
    }

    /**
     * 验证 URL 参数（预留扩展）
     *
     * @param  string  $url
     * @return bool
     */
    protected function validateUrl($url)
    {
        // 预留验证逻辑
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
