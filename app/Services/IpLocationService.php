<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpLocationService
{
    /**
     * 根据IP地址查询归属地
     * 优先使用 ip-api.com (国际)，失败则使用 Pconline (国内)
     */
    public function getLocation(string $ip): ?string
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === 'localhost' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return '本地';
        }

        // 尝试接口 1: ip.sb (基于 MaxMind，全球准确)
        try {
            // 需要设置 User-Agent
            $response = Http::timeout(5)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ])->get("https://api.ip.sb/geoip/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['country'])) {
                    $country = $data['country'];
                    // 常用国家英文转中文映射
                    $map = [
                        'United States' => '美国',
                        'China' => '中国',
                        'Hong Kong' => '香港',
                        'Taiwan' => '台湾',
                        'Japan' => '日本',
                        'South Korea' => '韩国',
                        'Korea' => '韩国',
                        'United Kingdom' => '英国',
                        'Germany' => '德国',
                        'France' => '法国',
                        'Russia' => '俄罗斯',
                        'Canada' => '加拿大',
                        'Australia' => '澳大利亚',
                        'Singapore' => '新加坡',
                        'Malaysia' => '马来西亚',
                        'Thailand' => '泰国',
                        'Vietnam' => '越南',
                        'Philippines' => '菲律宾',
                        'Indonesia' => '印度尼西亚',
                        'India' => '印度',
                        'Brazil' => '巴西',
                        'Netherlands' => '荷兰',
                        'Spain' => '西班牙',
                        'Italy' => '意大利',
                        // 可根据需求继续补充
                    ];

                    $countryCn = $map[$country] ?? $country;

                    $parts = array_filter([
                        $countryCn,
                        $data['region'] ?? '',
                        $data['city'] ?? '',
                    ]);
                    return implode(' ', $parts);
                }
            }
        } catch (\Exception $e) {
            Log::warning('IP查询失败 (ip.sb): ' . $e->getMessage());
        }

        // 尝试接口 2: ip-api.com (备选，中文支持好)
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'lang' => 'zh-CN',
                'fields' => 'status,country,regionName,city',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    $parts = array_filter([
                        $data['country'] ?? '',
                        $data['regionName'] ?? '',
                        $data['city'] ?? '',
                    ]);
                    return implode(' ', array_unique($parts));
                }
            }
        } catch (\Exception $e) {
            Log::warning('IP查询失败 (ip-api): ' . $e->getMessage());
        }

        return '未知归属地';
    }
}
