<?php

namespace App\Services\ImageSearch;

use Illuminate\Support\Facades\Http;
use Exception;

/**
 * 1688 搜同款服务类
 */
class Platform1688Service extends ImageSearchBaseService
{
    private $config;

    public function __construct()
    {
        $this->config = config('image_search.1688');
    }

    /**
     * 以图搜图
     *
     * @param  string  $imageBase64  Base64 编码的图片
     * @param  int  $page  页码
     * @param  int  $size  每页数量
     * @return array
     */
    public function searchByImage($imageBase64, $page = 1, $size = 20)
    {
        try {
            $params = $this->buildRequestParams($page, $size);
            $jsonData = ['imageBase64' => $imageBase64];

            $result = $this->sendRequest($params, $jsonData);

            if ($result && isset($result['success']) && $result['success']) {
                $dataCount = isset($result['data']) ? count($result['data']) : 0;
                return $this->successResponse(
                    $result['data'] ?? [],
                    "搜图成功，找到 {$dataCount} 条结果"
                );
            }

            return $this->errorResponse(
                $result['message'] ?? '搜图失败',
                $result['code'] ?? 500,
                $result['data'] ?? null
            );

        } catch (Exception $e) {
            return $this->errorResponse("请求异常: " . $e->getMessage(), 500);
        }
    }

    /**
     * URL 搜图
     *
     * @param  string  $url  图片 URL
     * @param  int  $page  页码
     * @param  int  $size  每页数量
     * @return array
     */
    public function searchByUrl($url, $page = 1, $size = 20)
    {
        try {
            if (!$this->validateUrl($url)) {
                return $this->errorResponse('图片 URL 格式不正确', 400);
            }

            $params = $this->buildRequestParams($page, $size);
            $params['url'] = $url;

            $result = $this->sendRequest($params);

            if ($result && isset($result['success']) && $result['success']) {
                $dataCount = isset($result['data']) ? count($result['data']) : 0;
                return $this->successResponse(
                    $result['data'] ?? [],
                    "搜图成功，找到 {$dataCount} 条结果"
                );
            }

            return $this->errorResponse(
                $result['message'] ?? '搜图失败',
                $result['code'] ?? 500,
                $result['data'] ?? null
            );

        } catch (Exception $e) {
            return $this->errorResponse("请求异常: " . $e->getMessage(), 500);
        }
    }

    /**
     * 构建请求参数
     *
     * @param  int  $page
     * @param  int  $size
     * @return array
     */
    private function buildRequestParams($page, $size)
    {
        $params = $this->config['default_params'];
        $params['page'] = (string) $page;
        $params['size'] = (string) $size;
        $params['sign'] = $this->config['sign'];

        return $params;
    }

    /**
     * 发送 HTTP 请求
     *
     * @param  array  $params
     * @param  array|null  $jsonData
     * @return array|null
     */
    private function sendRequest($params, $jsonData = null)
    {
        try {
            $request = Http::withHeaders($this->config['headers'])
                ->timeout(30);

            if ($jsonData) {
                $response = $request->post($this->config['base_url'], array_merge(
                    ['query' => http_build_query($params)],
                    $jsonData
                ));
            } else {
                $response = $request->post($this->config['base_url'] . '?' . http_build_query($params));
            }

            if ($response->successful()) {
                // 处理可能的 BOM
                $content = $response->body();
                $content = str_replace("\xEF\xBB\xBF", '', $content);

                return json_decode($content, true);
            }

            return [
                'success' => false,
                'code' => $response->status(),
                'message' => 'HTTP 请求失败',
                'data' => null,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'code' => 500,
                'message' => '请求失败: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
