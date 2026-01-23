<?php

return [
    "labels" => [
        "search" => "搜索",
        "base_url" => "基础 URL",
    ],

    "auth" => [
        "none" => "此 API 无需认证。",
        "instruction" => [
            "query" => <<<TEXT
                要进行请求验证，请在请求中包含查询参数 **`:parameterName`**。
                TEXT,
            "body" => <<<TEXT
                要进行请求验证，请在请求体中包含参数 **`:parameterName`**。
                TEXT,
            "query_or_body" => <<<TEXT
                要进行请求验证，请在查询字符串或请求体中包含参数 **`:parameterName`**。
                TEXT,
            "bearer" => <<<TEXT
                要进行请求验证，请包含一个 **`Authorization`** 头，其值为 **`"Bearer :placeholder"`**。
                TEXT,
            "basic" => <<<TEXT
                要进行请求验证，请包含形式为 **`"Basic {credentials}"`** 的 **`Authorization`** 头。
                `{credentials}` 的值应该是您的用户名/ID 和密码用冒号 (:) 连接，并进行 base64 编码后的字符串。
                TEXT,
            "header" => <<<TEXT
                要进行请求验证，请包含一个 **`:parameterName`** 头，其值为 **`":placeholder"`**。
                TEXT,
        ],
        "details" => <<<TEXT
            所有经过身份验证的端点都在下面的文档中标记了 `需要认证` 徽章。
            TEXT,
    ],

    "headings" => [
        "introduction" => "系统简介",
        "auth" => "用户认证指南",
    ],

    "endpoint" => [
        "request" => "请求",
        "headers" => "请求头 (Headers)",
        "url_parameters" => "URL 参数",
        "body_parameters" => "Body 参数",
        "query_parameters" => "Query 参数",
        "response" => "响应",
        "response_fields" => "响应字段",
        "example_request" => "请求示例",
        "example_response" => "响应示例",
        "responses" => [
            "binary" => "二进制数据",
            "empty" => "空响应",
        ],
    ],

    "try_it_out" => [
        "open" => "在线调试",
        "cancel" => "取消调试",
        "send" => "发送请求",
        "loading" => "请求处理中...",
        "received_response" => "接口返回数据",
        "request_failed" => "请求发生错误",
        "error_help" => <<<TEXT
            调试提示：如果请求失败，请检查网络连接是否正常。
            对于开发者：请确保 API 服务已启动并配置了正确的 CORS 跨域策略。
            更多错误详情请查看浏览器控制台。
            TEXT,
    ],

    "links" => [
        "postman" => "下载 Postman 集合",
        "openapi" => "下载 OpenAPI 规范",
    ],
];
