<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Redis;
use App\Services\FeimaoService;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;

class FeimaoCacheManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = '飞猫缓存';

    protected static ?string $title = '飞猫接口缓存管理';

    protected static ?string $slug = 'feimao-cache';

    protected static ?string $navigationGroup = '系统设置';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.feimao-cache-manager';

    public $searchKey = '';

    public function mount()
    {
        // 初始化检查或权限验证
    }

    #[Computed]
    public function cacheItems()
    {
        $prefix = config('database.redis.options.prefix', '');
        $keys = Redis::keys('feimao:*');
        $items = [];

        foreach ($keys as $fullKey) {
            $key = ($prefix && strpos($fullKey, $prefix) === 0)
                ? substr($fullKey, strlen($prefix))
                : $fullKey;

            $type = '未知类型';
            $color = 'gray';

            if (strpos($key, 'feimao:categories') !== false) {
                $type = '分类数据';
                $color = 'info';
            } elseif (strpos($key, 'feimao:product_list') !== false) {
                $type = '商品列表';
                $color = 'success';
            } elseif (strpos($key, 'feimao:sales_record') !== false) {
                $type = '销量记录';
                $color = 'warning';
            } elseif (strpos($key, 'feimao:auth_token') !== false) {
                $type = '身份令牌';
                $color = 'danger';
            } elseif (strpos($key, 'feimao:collect_product') !== false) {
                $type = '采集商品';
                $color = 'primary';
            }

            if ($this->searchKey && stripos($key, $this->searchKey) === false && stripos($type, $this->searchKey) === false) {
                continue;
            }

            $ttl = Redis::ttl($key);
            $rawContent = Redis::get($key);

            $parsedContent = json_decode($rawContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $parsedContent = ['response' => $rawContent];
            }

            $url = $parsedContent['url'] ?? '-';
            if ($type === '身份令牌')
                $url = 'https://feimaoxuanpin.com/api/system/login';

            $items[] = [
                'key' => $key,
                'short_key' => str_replace('feimao:', '', $key),
                'type' => $type,
                'color' => $color,
                'url' => $url,
                'ttl' => $ttl,
                'content' => $rawContent,
                'payload' => $parsedContent['payload'] ?? null,
                'response' => $parsedContent['response'] ?? $parsedContent,
                'can_refresh' => true,
            ];
        }

        return $items;
    }

    public function deleteItem($key)
    {
        Redis::del($key);
        Notification::make()
            ->title('缓存已删除')
            ->success()
            ->send();

        // Livewire 会自动重新渲染计算属性
    }

    public function refreshItem($key)
    {
        $prefix = config('database.redis.options.prefix', '');
        $pureKey = str_replace($prefix, '', $key);
        $parts = explode(':', $pureKey);

        $service = app(FeimaoService::class);

        try {
            $type = $parts[1] ?? '';

            if ($type === 'categories') {
                $parentId = (int) ($parts[2] ?? 0);
                Redis::del($key);
                $service->getCategories($parentId);
            } elseif ($type === 'product_list') {
                $categoryId = $parts[2] ?? '';
                $pageNum = (int) ($parts[3] ?? 1);
                $pageSize = (int) ($parts[4] ?? 10);
                Redis::del($key);
                $service->getProductListByCategory($pageNum, $pageSize, $categoryId);
            } elseif ($type === 'sales_record') {
                $productId = $parts[2] ?? '';
                Redis::del($key);
                $service->getSalesRecord($productId);
            } elseif ($type === 'auth_token') {
                Redis::del($key);
                $service->getToken(true);
            } elseif ($type === 'collect_product') {
                Redis::del($key);
                Notification::make()
                    ->title('缓存已删除')
                    ->body('采集商品缓存依赖商品ID参数,已删除缓存,下次查询时会自动重新缓存')
                    ->success()
                    ->send();
                return;
            } else {
                Notification::make()
                    ->title('不支持刷新此类型')
                    ->warning()
                    ->send();
                return;
            }

            Notification::make()
                ->title('缓存已刷新')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('刷新失败')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
