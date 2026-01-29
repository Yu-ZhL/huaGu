<?php

namespace App\Http\Controllers;

use App\Services\FeimaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class FeimaoCacheController extends Controller
{
    protected $feimaoService;

    public function __construct(FeimaoService $feimaoService)
    {
        $this->feimaoService = $feimaoService;
    }

    public function index()
    {
        $keys = Redis::keys('feimao:*');
        $cacheItems = [];

        foreach ($keys as $key) {
            // Remove prefix if Redis adds app name prefix, but usually Redis::keys returns full key or relative depending on config.
            // Laravel Redis facade usually handles prefix. Let's assume keys are as stored.
            // Note: Redis::keys return keys with prefix if configured.
            // A safer way is to just display what we get, or strip known prefix.

            // Allow easier reading of key type
            $type = 'Unknown';
            if (strpos($key, 'feimao:categories') !== false)
                $type = '分类/Categories';
            elseif (strpos($key, 'feimao:product_list') !== false)
                $type = '商品列表/Product List';
            elseif (strpos($key, 'feimao:sales_record') !== false)
                $type = '销量记录/Sales Record';
            elseif (strpos($key, 'feimao:auth_token') !== false)
                $type = 'Auth Token';

            $ttl = Redis::ttl($key);

            $cacheItems[] = [
                'key' => $key,
                'short_key' => str_replace(config('database.redis.options.prefix', ''), '', $key), // Attempt to show short key
                'type' => $type,
                'ttl' => $ttl
            ];
        }

        return Inertia::render('Feimao/CacheManager', [
            'cacheItems' => $cacheItems
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['key' => 'required|string']);
        Redis::del($request->key);
        return redirect()->back()->with('success', '缓存已删除');
    }

    public function refresh(Request $request)
    {
        $request->validate(['key' => 'required|string']);
        $key = $request->input('key');

        // Logic to parse key and call service
        // Key format: feimao:categories:{parentId}
        // feimao:product_list:{categoryId}:{pageNum}:{pageSize}
        // feimao:sales_record:{productId}

        // We need to strip the Laravel prefix if it exists to parse parameters safely
        $prefix = config('database.redis.options.prefix', '');
        $pureKey = str_replace($prefix, '', $key);

        $parts = explode(':', $pureKey);

        // parts[0] = feimao
        // parts[1] = type

        if (!isset($parts[1])) {
            return redirect()->back()->with('error', '无法识别的缓存键格式');
        }

        $type = $parts[1];

        try {
            if ($type === 'categories') {
                $parentId = (int) ($parts[2] ?? 0);
                // Force refresh: We can optimize Service to accept a force flag or just let it update key
                // Current service implementation: getCategories checks cache first. 
                // To force refresh, we should verify if we deleted it first? 
                // Actually, the user wants "Refresh". The service doesn't have a "force" param on get methods except getToken.
                // We should probably just delete it then call get.
                Redis::del($key);
                $this->feimaoService->getCategories($parentId);
            } elseif ($type === 'product_list') {
                $categoryId = $parts[2] ?? '';
                $pageNum = (int) ($parts[3] ?? 1);
                $pageSize = (int) ($parts[4] ?? 10);
                Redis::del($key);
                $this->feimaoService->getProductListByCategory($pageNum, $pageSize, $categoryId);
            } elseif ($type === 'sales_record') {
                $productId = $parts[2] ?? '';
                Redis::del($key);
                $this->feimaoService->getSalesRecord($productId);
            } elseif ($type === 'auth_token') {
                // Token refresh
                Redis::del($key);
                $this->feimaoService->getToken(true);
            } else {
                return redirect()->back()->with('error', '不支持刷新此类型的缓存');
            }

            return redirect()->back()->with('success', '缓存已刷新');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', '刷新失败: ' . $e->getMessage());
        }
    }
}
