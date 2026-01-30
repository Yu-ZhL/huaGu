<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use File;

/**
 * @group 系统设置
 *
 * 包含专属客服、使用教程和插件下载等配置接口
 */
class SettingController extends Controller
{
    /**
     * 获取专属客服
     *
     * 获取客服二维码图片及其底部文案。
     *
     * @response {
     *  "qr_code": "http://domain.com/storage/qr.jpg",
     *  "text": "扫码关注"
     * }
     */
    public function getCustomerService()
    {
        $setting = SiteSetting::get('customer_service', ['qr_code' => '', 'text' => '']);

        if (!empty($setting['qr_code'])) {
            $qrCodePath = $setting['qr_code'];

            // 如果已经是完整URL,直接返回
            if (filter_var($qrCodePath, FILTER_VALIDATE_URL)) {
                // 已经是完整URL,不需要处理
            }
            // 如果路径以 http:// 或 https:// 开头
            elseif (str_starts_with($qrCodePath, 'http://') || str_starts_with($qrCodePath, 'https://')) {
                // 已经是完整URL,不需要处理
            }
            // 如果路径以 storage/ 开头,去掉它
            elseif (str_starts_with($qrCodePath, 'storage/')) {
                $qrCodePath = substr($qrCodePath, 8); // 去掉 'storage/'
                $setting['qr_code'] = asset('storage/' . $qrCodePath);
            }
            // 其他情况,直接添加 storage/ 前缀
            else {
                $setting['qr_code'] = asset('storage/' . $qrCodePath);
            }
        }

        return response()->json($setting);
    }

    /**
     * 获取使用教程
     *
     * 获取教程视频的 URL。
     *
     * @response {
     *  "video_url": "http://domain.com/video/flyingCat_compressed.mp4"
     * }
     */
    public function getUsageTutorial()
    {
        $path = SiteSetting::get('usage_tutorial', 'video/flyingCat_compressed.mp4');
        return response()->json([
            'video_url' => asset($path)
        ]);
    }

    /**
     * 下载飞猫选品插件
     *
     * 动态打包最新的插件 dist 目录并提供 Zip 下载。
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadExtension()
    {
        $sourcePath = public_path('feimao-extension/dist');
        $zipFileName = 'feimao-extension.zip';
        $tempZip = storage_path('app/temp/' . $zipFileName);

        // 确保临时目录存在
        if (!File::exists(storage_path('app/temp'))) {
            File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = File::allFiles($sourcePath);
            foreach ($files as $file) {
                $relativePath = substr($file->getRealPath(), strlen($sourcePath) + 1);
                $zip->addFile($file->getRealPath(), $relativePath);
            }
            $zip->close();
        }

        return response()->download($tempZip, $zipFileName)->deleteFileAfterSend(true);
    }
}
