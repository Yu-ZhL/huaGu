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
            return '本地/内网';
        }

        // 尝试接口 1: ip-api.com (国际通用，支持 JSON)
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

        // 尝试接口 2: 太平洋网络 (国内稳定，GBK编码)
        try {
            $response = Http::timeout(3)->get("http://whois.pconline.com.cn/ipJson.jsp", [
                'ip' => $ip,
                'json' => 'true',
            ]);

            if ($response->successful()) {
                $body = $response->body();
                // 转换编码 GBK -> UTF-8
                $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                $data = json_decode($body, true);

                if (!empty($data['addr'])) {
                    return trim($data['addr']);
                }
                if (!empty($data['pro']) || !empty($data['city'])) {
                    return trim(($data['pro'] ?? '') . ' ' . ($data['city'] ?? ''));
                }
            }
        } catch (\Exception $e) {
            Log::warning('IP查询失败 (pconline): ' . $e->getMessage());
        }

        return '未知归属地';
    }
}
