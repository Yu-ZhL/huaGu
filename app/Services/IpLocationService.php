<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpLocationService
{
    /**
     * 根据IP地址查询归属地
     * 使用免费的ip-api.com服务
     */
    public function getLocation(string $ip): ?string
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === 'localhost') {
            return '本地';
        }

        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'lang' => 'zh-CN',
                'fields' => 'status,country,regionName,city',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success') {
                    $parts = array_filter([
                        $data['country'] ?? '',
                        $data['regionName'] ?? '',
                        $data['city'] ?? '',
                    ]);

                    return implode(' ', $parts) ?: '未知';
                }
            }
        } catch (\Exception $e) {
            Log::warning('IP归属地查询失败', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return '未知';
    }
}
