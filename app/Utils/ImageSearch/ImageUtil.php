<?php

namespace App\Utils\ImageSearch;

use Exception;

/**
 * 图片处理工具类
 */
class ImageUtil
{
    /**
     * 上传的文件转 Base64
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     * @throws Exception
     */
    public static function fileToBase64($file)
    {
        try {
            $path = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension());

            // 特殊格式需要转换
            if (in_array($extension, ['avif', 'heic', 'heif', 'webp'])) {
                return self::convertAndEncode($path, $extension);
            }

            // 常规格式直接读
            $imageData = file_get_contents($path);
            return base64_encode($imageData);

        } catch (Exception $e) {
            throw new Exception("图片处理失败: " . $e->getMessage());
        }
    }

    /**
     * URL 图片转 Base64
     *
     * @param  string  $url
     * @return string
     * @throws Exception
     */
    public static function urlToBase64($url)
    {
        try {
            $imageData = file_get_contents($url);
            if ($imageData === false) {
                throw new Exception("无法获取图片");
            }

            // 检测图片格式
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);

            // 特殊格式转换
            if (in_array($mimeType, ['image/avif', 'image/heic', 'image/heif', 'image/webp'])) {
                $tempFile = tempnam(sys_get_temp_dir(), 'img_');
                file_put_contents($tempFile, $imageData);
                $result = self::convertAndEncode($tempFile, $mimeType);
                unlink($tempFile);
                return $result;
            }

            return base64_encode($imageData);

        } catch (Exception $e) {
            throw new Exception("URL 图片处理失败: " . $e->getMessage());
        }
    }

    /**
     * 转换特殊格式并编码
     *
     * @param  string  $path
     * @param  string  $format
     * @return string
     * @throws Exception
     */
    private static function convertAndEncode($path, $format)
    {
        // 优先用 Imagick
        if (extension_loaded('imagick')) {
            return self::convertWithImagick($path);
        }

        // 降级用 GD
        if (extension_loaded('gd')) {
            return self::convertWithGD($path, $format);
        }

        // 都没有就直接读
        $imageData = file_get_contents($path);
        return base64_encode($imageData);
    }

    /**
     * 用 Imagick 转换
     *
     * @param  string  $path
     * @return string
     * @throws Exception
     */
    private static function convertWithImagick($path)
    {
        try {
            $imagick = new \Imagick($path);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(95);
            $blob = $imagick->getImageBlob();
            $imagick->clear();
            $imagick->destroy();

            return base64_encode($blob);
        } catch (Exception $e) {
            throw new Exception("Imagick 转换失败: " . $e->getMessage());
        }
    }

    /**
     * 用 GD 转换
     *
     * @param  string  $path
     * @param  string  $format
     * @return string
     * @throws Exception
     */
    private static function convertWithGD($path, $format)
    {
        try {
            // GD 对 webp 有支持
            if ($format === 'webp' && function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($path);
            } else {
                // 其他格式尝试通用方法
                $image = imagecreatefromstring(file_get_contents($path));
            }

            if (!$image) {
                throw new Exception("GD 无法读取图片");
            }

            ob_start();
            imagejpeg($image, null, 95);
            $imageData = ob_get_clean();
            imagedestroy($image);

            return base64_encode($imageData);
        } catch (Exception $e) {
            throw new Exception("GD 转换失败: " . $e->getMessage());
        }
    }

    /**
     * 验证 Base64 图片数据
     *
     * @param  string  $base64
     * @return bool
     */
    public static function validateBase64($base64)
    {
        if (empty($base64)) {
            return false;
        }

        // 移除可能的 data URI 前缀
        if (strpos($base64, 'data:image') === 0) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            return false;
        }

        // 检查是否为有效图片
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($decoded);

        return strpos($mimeType, 'image/') === 0;
    }
}
