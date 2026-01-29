<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

class FeimaoService
{
    private $baseUrl = 'https://feimaoxuanpin.com';
    private $loginUrl = '/api/system/login';

    // 登录配置
    private $account = '17670808581';
    private $password = '123456';
    private $platform = 'web';

    private $tokenKey = 'feimao:auth_token';
    private $tokenBuffer = 300; // 缓冲时间

    private function getHeaders()
    {
        return [
            'authority' => 'feimaoxuanpin.com',
            'accept' => 'application/json, text/plain, */*',
            'content-type' => 'application/json',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'referer' => 'https://feimaoxuanpin.com/index',
            'origin' => 'https://feimaoxuanpin.com',
        ];
    }

    // 获取Token，优先读缓存
    public function getToken($forceRefresh = false)
    {
        if (!$forceRefresh) {
            $token = Redis::get($this->tokenKey);
            if ($token) {
                return $token;
            }
        }

        return $this->login();
    }

    // 登录并缓存Token
    public function login()
    {
        try {
            // Log::info('FeimaoService: 正在登录...');

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . $this->loginUrl, [
                    'account' => $this->account,
                    'password' => $this->password,
                    'platform' => $this->platform,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['code'] ?? 0) == 200 && !empty($data['data']['token'])) {
                    $token = $data['data']['token'];
                    // 缓存永不过期，除非接口报错402才刷新
                    Redis::set($this->tokenKey, $token);
                    return $token;
                }
            }

            Log::error('FeimaoService: 登录失败', ['response' => $response->body()]);
            return null;

        } catch (Exception $e) {
            Log::error('FeimaoService: 登录异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // 发起请求，自动处理Token刷新
    public function request($method, $uri, $data = [], $retry = 0)
    {
        $token = $this->getToken();

        $headers = $this->getHeaders();
        if ($token) {
            $headers['authorization'] = $token;
        }

        try {
            $client = Http::withHeaders($headers);

            if (strtoupper($method) === 'POST') {
                $response = $client->post($this->baseUrl . $uri, $data);
            } else {
                $response = $client->get($this->baseUrl . $uri, $data);
            }

            $result = $response->json();

            // 402 Token失效，刷新重试
            if (isset($result['code']) && $result['code'] == 402 && $retry < 1) {
                $this->login();
                return $this->request($method, $uri, $data, $retry + 1);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('FeimaoService: 请求异常', ['url' => $uri, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getProductList(array $productIds, int $pageNum = 1, int $pageSize = 40)
    {
        $url = '/api/collectProduct/list';
        $payload = [
            'channel' => 'temu',
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
            'productIds' => $productIds
        ];

        return $this->request('POST', $url, $payload);
    }

    // 获取分类
    public function getCategories(int $parentId = 0)
    {
        $cacheKey = "feimao:categories:{$parentId}";
        $url = '/api/bestSellingProducts/category';
        $payload = ['parentId' => $parentId];

        $cachedData = Redis::get($cacheKey);
        if ($cachedData) {
            $data = json_decode($cachedData, true);
            // 兼容旧格式或提取响应内容
            return $data['response'] ?? $data;
        }

        $result = $this->request('POST', $url, $payload);

        // 响应正常且数据不为空才存入缓存
        if (isset($result['code']) && $result['code'] == 200 && !empty($result['data'])) {
            $cacheContent = [
                'url' => $this->baseUrl . $url,
                'payload' => $payload,
                'response' => $result,
                'updated_at' => now()->toDateTimeString(),
            ];
            Redis::set($cacheKey, json_encode($cacheContent));
        }

        return $result;
    }

    // 获取分类下的商品列表
    public function getProductListByCategory(int $pageNum, int $pageSize, string $categoryId = '')
    {
        $cacheKey = "feimao:product_list:{$categoryId}:{$pageNum}:{$pageSize}";
        $url = '/api/bestSellingProducts/list';
        $payload = [
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
            'categoryId' => $categoryId
        ];

        $cachedData = Redis::get($cacheKey);
        if ($cachedData) {
            $data = json_decode($cachedData, true);
            return $data['response'] ?? $data;
        }

        $result = $this->request('POST', $url, $payload);

        // 响应正常且 records 不为空才存缓存
        if (isset($result['code']) && $result['code'] == 200 && !empty($result['data']['records'])) {
            $cacheContent = [
                'url' => $this->baseUrl . $url,
                'payload' => $payload,
                'response' => $result,
                'updated_at' => now()->toDateTimeString(),
            ];
            Redis::setex($cacheKey, 86400, json_encode($cacheContent));
        }

        return $result;
    }

    // 获取销量记录
    public function getSalesRecord(string $productId)
    {
        $cacheKey = "feimao:sales_record:{$productId}";
        $url = '/api/bestSellingProducts/salesRecord';
        $payload = ['productId' => $productId];

        $cachedData = Redis::get($cacheKey);
        if ($cachedData) {
            $data = json_decode($cachedData, true);
            return $data['response'] ?? $data;
        }

        $result = $this->request('POST', $url, $payload);

        // 响应正常且数据不为空才存缓存
        if (isset($result['code']) && $result['code'] == 200 && !empty($result['data'])) {
            $cacheContent = [
                'url' => $this->baseUrl . $url,
                'payload' => $payload,
                'response' => $result,
                'updated_at' => now()->toDateTimeString(),
            ];
            Redis::setex($cacheKey, 86400, json_encode($cacheContent));
        }

        return $result;
    }
}
