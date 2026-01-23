<?php

namespace App\Http\Requests\ImageSearch;

use Illuminate\Foundation\Http\FormRequest;

/**
 * URL 搜图请求验证类
 * 
 * 预留验证规则，待后续完善
 */
class UrlSearchRequest extends FormRequest
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
            // 'url' => 'required|url',
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
            // 'url.required' => '请提供图片 URL',
            // 'url.url' => 'URL 格式不正确',
            // 'page.integer' => '页码必须为整数',
            // 'page.min' => '页码最小为 1',
            // 'size.integer' => '每页数量必须为整数',
            // 'size.min' => '每页数量最小为 1',
            // 'size.max' => '每页数量最大为 100',
        ];
    }
}
