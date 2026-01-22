<?php

namespace App\Http\Requests\ImageSearch;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 以图搜图请求验证类
 * 
 * 预留验证规则，待后续完善
 */
class ImageSearchRequest extends FormRequest
{
    /**
     * 是否授权此请求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 验证规则
     */
    public function rules(): array
    {
        return [
            // 预留验证规则
            // 'image' => 'required_without:image_base64|file|image|max:10240',
            // 'image_base64' => 'required_without:image|string',
            // 'page' => 'integer|min:1',
            // 'size' => 'integer|min:1|max:100',
        ];
    }

    /**
     * 错误消息
     */
    public function messages(): array
    {
        return [
            // 'image.required_without' => '请上传图片文件或提供 Base64 数据',
            // 'image.file' => '图片格式不正确',
            // 'image.image' => '只支持图片文件',
            // 'image.max' => '图片大小不能超过 10MB',
            // 'page.integer' => '页码必须为整数',
            // 'page.min' => '页码最小为 1',
            // 'size.integer' => '每页数量必须为整数',
            // 'size.min' => '每页数量最小为 1',
            // 'size.max' => '每页数量最大为 100',
        ];
    }
}
