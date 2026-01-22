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
            所有经过身份验证的端点都在下面的文档中标记了 `requires authentication` 徽章。
            TEXT,
    ],

    "headings" => [
        "introduction" => "简介",
        "auth" => "验证请求",
    ],

    "endpoint" => [
        "request" => "请求",
        "headers" => "Headers",
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
        "open" => "试一试 ⚡",
        "cancel" => "取消 🛑",
        "send" => "发送请求 💥",
        "loading" => "⏱ 发送中...",
        "received_response" => "收到响应",
        "request_failed" => "请求失败，错误信息",
        "error_help" => <<<TEXT
            提示：请检查您的网络连接是否正常。
            如果您是此 API 的维护者，请确认您的 API 正在运行并已启用 CORS。
            您可以查看开发者工具控制台获取调试信息。
            TEXT,
    ],

    "links" => [
        "postman" => "查看 Postman 集合",
        "openapi" => "查看 OpenAPI 规范",
    ],
];
