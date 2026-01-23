<?php

use Knuckles\Scribe\Extracting\Strategies;
use Knuckles\Scribe\Config\Defaults;
use function Knuckles\Scribe\Config\{removeStrategies, configureStrategy};

// 仅显示最常用的配置。查看 https://scribe.knuckles.wtf/laravel/reference/config 了解所有配置项。

return [
    // 生成文档的 HTML <title>
    'title' => config('app.name') . ' API 文档',

    // API 的简短描述。将包含在文档网页、Postman 集合和 OpenAPI 规范中。
    'description' => '已经实现了标准化的搜同款(以图搜图)接口系统，支持多平台商品搜索功能。当前已完成 1688 平台的接口实现，并为其他 24 个平台预留了标准化的接口框架。',

    // 放置在"介绍"部分的文本，紧接在 `description` 之后。支持 Markdown 和 HTML。
    'intro_text' => <<<INTRO
        API 文档

        <aside>在页面滚动时，您可以在右侧的黑色区域（手机端则在正文中）看到不同编程语言的 API 调用示例。 您可以点击右上角的标签页来切换语言（手机端请通过左上角的菜单栏进行切换）。</aside>
    INTRO,

    // 文档中显示的基础 URL。
    // 如果您使用的是 `laravel` 类型，可以将其设置为动态字符串，例如 '{{ config("app.tenant_url") }}' 以获取动态基础 URL。
    'base_url' => config("app.url"),

    // 要包含在文档中的路由
    'routes' => [
        [
            'match' => [
                // 仅匹配路径符合此模式的路由（使用 * 作为通配符匹配任何字符）。例如：'users/*'。
                'prefixes' => ['api/*'],

                // 仅匹配域名符合此模式的路由（使用 * 作为通配符匹配任何字符）。例如：'api.*'。
                'domains' => ['*'],
            ],

            // 即使不符合上述规则，也包含这些路由。
            'include' => [
                // 'users.index', 'POST /new', '/auth/*'
            ],

            // 即使符合上述规则，也排除这些路由。
            'exclude' => [
                // 'GET /health', 'admin.*'
            ],
        ],
    ],

    // 要生成的文档输出类型。
    // - "static" 将在 /public/docs 文件夹中生成静态 HTML 页面，
    // - "laravel" 将生成文档作为 Blade 视图，因此您可以添加路由和身份验证。
    // - "external_static" 和 "external_laravel" 执行与上述相同的操作，但将 OpenAPI 规范作为 URL 传递给外部 UI 模板
    'type' => 'static',

    // 查看 https://scribe.knuckles.wtf/laravel/reference/config#theme 了解支持的选项
    'theme' => 'default',

    'static' => [
        // HTML 文档、资源和 Postman 集合将生成到此文件夹。
        // 源 Markdown 仍将在 resources/docs 中。
        'output_path' => 'public/docs',
    ],

    'laravel' => [
        // 是否自动为您创建文档路由以查看生成的文档。您仍然可以手动设置路由。
        'add_routes' => true,

        // 用于文档端点的 URL 路径（如果 `add_routes` 为 true）。
        // 默认情况下，`/docs` 打开 HTML 页面，`/docs.postman` 打开 Postman 集合，`/docs.openapi` 打开 OpenAPI 规范。
        'docs_url' => '/docs',

        // 在 `public` 中存储 CSS 和 JS 资源的目录。
        // 默认情况下，资源存储在 `public/vendor/scribe` 中。
        // 如果设置，资源将存储在 `public/{{assets_directory}}` 中
        'assets_directory' => null,

        // 附加到文档端点的中间件（如果 `add_routes` 为 true）。
        'middleware' => [],
    ],

    'external' => [
        'html_attributes' => []
    ],

    'try_it_out' => [
        // 为您的端点添加"在线测试"按钮，以便用户可以直接从浏览器测试端点。
        // 不要忘记为您的端点启用 CORS 标头。
        'enabled' => true,

        // 在 API 测试器中使用的基础 URL。留空将与显示的 URL 相同（`scribe.base_url`）。
        'base_url' => null,

        // [Laravel Sanctum] 在每个请求之前获取 CSRF 令牌，并将其添加为 X-XSRF-TOKEN 标头。
        'use_csrf' => false,

        // 获取 CSRF 令牌的 URL（如果 `use_csrf` 为 true）。
        'csrf_url' => '/sanctum/csrf-cookie',
    ],

    // API 如何进行身份验证？此信息将用于显示的文档、生成的示例和响应调用中。
    'auth' => [
        // 如果 API 中的任何端点使用身份验证，请将其设置为 true。
        'enabled' => false,

        // 如果您的 API 默认应进行身份验证，请将其设置为 true。如果是，您还必须将 `enabled`（上面）设置为 true。
        // 然后，您可以在单个端点上使用 @unauthenticated 或 @authenticated 来更改其默认状态。
        'default' => false,

        // 身份验证值应该在请求中的哪里发送？
        'in' => 'bearer',

        // 身份验证参数（例如 token、key、apiKey）或标头（例如 Authorization、Api-Key）的名称。
        'name' => 'key',

        // Scribe 用于验证响应调用的参数值。
        // 这不会包含在生成的文档中。如果为空，Scribe 将使用随机值。
        'use_value' => env('SCRIBE_AUTH_KEY'),

        // 用户将在示例请求中看到的身份验证参数的占位符。
        // 如果您希望 Scribe 使用随机值作为占位符，请将其设置为 null。
        'placeholder' => '{YOUR_AUTH_KEY}',

        // 为用户提供的任何额外身份验证相关信息。支持 Markdown 和 HTML。
        'extra_info' => '您可以通过访问仪表板并点击 <b>生成 API 令牌</b> 来获取您的令牌。',
    ],

    // 每个端点的示例请求将以这些语言显示。
    // 支持的选项有：bash、javascript、php、python
    // 要添加您自己的语言，请参见 https://scribe.knuckles.wtf/laravel/advanced/example-requests
    // 注意：不适用于 `external` 文档类型
    'example_languages' => [
        'bash',
        'javascript',
    ],

    // 除了 HTML 文档之外，还生成 Postman 集合（v2.1.0）。
    // 对于 'static' 文档，集合将生成到 public/docs/collection.json。
    // 对于 'laravel' 文档，它将生成到 storage/app/scribe/collection.json。
    // 将 `laravel.add_routes` 设置为 true（上面）也将添加集合的路由。
    'postman' => [
        'enabled' => true,

        'overrides' => [
            // 'info.version' => '2.0.0',
        ],
    ],

    // 除了文档网页之外，还生成 OpenAPI 规范。
    // 对于 'static' 文档，集合将生成到 public/docs/openapi.yaml。
    // 对于 'laravel' 文档，它将生成到 storage/app/scribe/openapi.yaml。
    // 将 `laravel.add_routes` 设置为 true（上面）也将添加规范的路由。
    'openapi' => [
        'enabled' => true,

        // 要生成的 OpenAPI 规范版本。支持的版本：'3.0.3'、'3.1.0'。
        // OpenAPI 3.1 与 JSON Schema 更兼容，正在成为主导版本。
        // 有关 3.1 更改的详细信息，请参见 https://spec.openapis.org/oas/v3.1.0。
        'version' => '3.0.3',

        'overrides' => [
            // 'info.version' => '2.0.0',
        ],

        // 生成 OpenAPI 规范时要使用的其他生成器。
        // 应扩展 `Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator`。
        'generators' => [],
    ],

    'groups' => [
        // 没有 @group 的端点将放置在此默认组中。
        'default' => '其他接口',

        // 默认情况下，Scribe 将按字母顺序对组进行排序，并按照定义路由的顺序对端点进行排序。
        // 您可以通过在此处按您想要的顺序列出组、子组和端点来覆盖此设置。
        // 有关详细信息，请参见 https://scribe.knuckles.wtf/blog/laravel-v4#easier-sorting 和 https://scribe.knuckles.wtf/laravel/reference/config#order
        // 注意：不适用于 `external` 文档类型
        'order' => [],
    ],

    // 自定义徽标路径。这将用作 <img> 标签的 src 属性的值，
    // 因此请确保它指向可访问的 URL 或路径。设置为 false 以不使用徽标。
    // 例如，如果您的徽标在 public/img 中：
    // - 'logo' => '../img/logo.png' // 用于 `static` 类型（输出文件夹是 public/docs）
    // - 'logo' => 'img/logo.png' // 用于 `laravel` 类型
    'logo' => false,

    // 通过指定标记和格式来自定义文档中显示的"最后更新"值。
    // 示例：
    // - {date:F j Y} => March 28, 2022
    // - {git:short} => 最后一次 Git 提交的短哈希
    // 可用的标记是 `{date:<format>}` 和 `{git:<format>}`。
    // 您传递给 `date` 的格式将传递给 PHP 的 `date()` 函数。
    // 您传递给 `git` 的格式可以是"short"或"long"。
    // 注意：不适用于 `external` 文档类型
    'last_updated' => '最后更新时间: {date:Y年m月d日}',

    'examples' => [
        // 将其设置为任何数字，以便在每次运行时为参数生成相同的示例值，
        'faker_seed' => 1234,

        // 使用 API 资源和转换器，Scribe 尝试生成示例模型以在您的 API 响应中使用。
        // 默认情况下，Scribe 将尝试模型的工厂，如果失败，则尝试从数据库中获取第一个。
        // 您可以在此处重新排序或删除策略。
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],

    // Scribe 在每个阶段用于提取有关路由的信息的策略。
    // 使用 configureStrategy() 为列表中的策略指定设置。
    // 使用 removeStrategies() 删除包含的策略。
    'strategies' => [
        'metadata' => [
            ...Defaults::METADATA_STRATEGIES,
        ],
        'headers' => [
            ...Defaults::HEADERS_STRATEGIES,
            Strategies\StaticData::withSettings(data: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
        ],
        'urlParameters' => [
            ...Defaults::URL_PARAMETERS_STRATEGIES,
        ],
        'queryParameters' => [
            ...Defaults::QUERY_PARAMETERS_STRATEGIES,
        ],
        'bodyParameters' => [
            ...Defaults::BODY_PARAMETERS_STRATEGIES,
        ],
        'responses' => configureStrategy(
            Defaults::RESPONSES_STRATEGIES,
            Strategies\Responses\ResponseCalls::withSettings(
                only: ['GET *'],
                // 推荐：在响应调用中禁用调试模式以避免响应中的错误堆栈跟踪
                config: [
                    'app.debug' => false,
                ]
            )
        ),
        'responseFields' => [
            ...Defaults::RESPONSE_FIELDS_STRATEGIES,
        ]
    ],

    // 对于响应调用、API 资源响应和转换器响应，
    // Scribe 将尝试启动数据库事务，因此不会将任何更改持久化到您的数据库。
    // 在此处告诉 Scribe 应该进行事务的连接。如果您只使用一个数据库连接，可以保持原样。
    'database_connections_to_transact' => [config('database.default')],

    'fractal' => [
        // 如果您使用的是带 league/fractal 的自定义序列化器，可以在此处指定它。
        'serializer' => null,
    ],
];


// return [
//     // The HTML <title> for the generated documentation.
//     'title' => config('app.name') . ' API 文档',

//     // A short description of your API. Will be included in the docs webpage, Postman collection and OpenAPI spec.
//     'description' => '已经实现了标准化的搜同款(以图搜图)接口系统，支持多平台商品搜索功能。当前已完成 1688 平台的接口实现，并为其他 24 个平台预留了标准化的接口框架。',

//     // Text to place in the "Introduction" section, right after the `description`. Markdown and HTML are supported.
//     'intro_text' => <<<INTRO
//         api文档

//         <aside>在页面滚动时，您可以在右侧的黑色区域（手机端则在正文中）看到不同编程语言的 API 调用示例。 您可以点击右上角的标签页来切换语言（手机端请通过左上角的菜单栏进行切换）。</aside>
//     INTRO,

//     // The base URL displayed in the docs.
//     // If you're using `laravel` type, you can set this to a dynamic string, like '{{ config("app.tenant_url") }}' to get a dynamic base URL.
//     'base_url' => config("app.url"),

//     // Routes to include in the docs
//     'routes' => [
//         [
//             'match' => [
//                 // Match only routes whose paths match this pattern (use * as a wildcard to match any characters). Example: 'users/*'.
//                 'prefixes' => ['api/*'],

//                 // Match only routes whose domains match this pattern (use * as a wildcard to match any characters). Example: 'api.*'.
//                 'domains' => ['*'],
//             ],

//             // Include these routes even if they did not match the rules above.
//             'include' => [
//                 // 'users.index', 'POST /new', '/auth/*'
//             ],

//             // Exclude these routes even if they matched the rules above.
//             'exclude' => [
//                 // 'GET /health', 'admin.*'
//             ],
//         ],
//     ],

//     // The type of documentation output to generate.
//     // - "static" will generate a static HTMl page in the /public/docs folder,
//     // - "laravel" will generate the documentation as a Blade view, so you can add routing and authentication.
//     // - "external_static" and "external_laravel" do the same as above, but pass the OpenAPI spec as a URL to an external UI template
//     'type' => 'static',

//     // See https://scribe.knuckles.wtf/laravel/reference/config#theme for supported options
//     'theme' => 'default',

//     'static' => [
//         // HTML documentation, assets and Postman collection will be generated to this folder.
//         // Source Markdown will still be in resources/docs.
//         'output_path' => 'public/docs',
//     ],

//     'laravel' => [
//         // Whether to automatically create a docs route for you to view your generated docs. You can still set up routing manually.
//         'add_routes' => true,

//         // URL path to use for the docs endpoint (if `add_routes` is true).
//         // By default, `/docs` opens the HTML page, `/docs.postman` opens the Postman collection, and `/docs.openapi` the OpenAPI spec.
//         'docs_url' => '/docs',

//         // Directory within `public` in which to store CSS and JS assets.
//         // By default, assets are stored in `public/vendor/scribe`.
//         // If set, assets will be stored in `public/{{assets_directory}}`
//         'assets_directory' => null,

//         // Middleware to attach to the docs endpoint (if `add_routes` is true).
//         'middleware' => [],
//     ],

//     'external' => [
//         'html_attributes' => []
//     ],

//     'try_it_out' => [
//         // Add a Try It Out button to your endpoints so consumers can test endpoints right from their browser.
//         // Don't forget to enable CORS headers for your endpoints.
//         'enabled' => true,

//         // The base URL to use in the API tester. Leave as null to be the same as the displayed URL (`scribe.base_url`).
//         'base_url' => null,

//         // [Laravel Sanctum] Fetch a CSRF token before each request, and add it as an X-XSRF-TOKEN header.
//         'use_csrf' => false,

//         // The URL to fetch the CSRF token from (if `use_csrf` is true).
//         'csrf_url' => '/sanctum/csrf-cookie',
//     ],

//     // How is your API authenticated? This information will be used in the displayed docs, generated examples and response calls.
//     'auth' => [
//         // Set this to true if ANY endpoints in your API use authentication.
//         'enabled' => false,

//         // Set this to true if your API should be authenticated by default. If so, you must also set `enabled` (above) to true.
//         // You can then use @unauthenticated or @authenticated on individual endpoints to change their status from the default.
//         'default' => false,

//         // Where is the auth value meant to be sent in a request?
//         'in' => AuthIn::BEARER->value,

//         // The name of the auth parameter (e.g. token, key, apiKey) or header (e.g. Authorization, Api-Key).
//         'name' => 'key',

//         // The value of the parameter to be used by Scribe to authenticate response calls.
//         // This will NOT be included in the generated documentation. If empty, Scribe will use a random value.
//         'use_value' => env('SCRIBE_AUTH_KEY'),

//         // Placeholder your users will see for the auth parameter in the example requests.
//         // Set this to null if you want Scribe to use a random value as placeholder instead.
//         'placeholder' => '{YOUR_AUTH_KEY}',

//         // Any extra authentication-related info for your users. Markdown and HTML are supported.
//         'extra_info' => 'You can retrieve your token by visiting your dashboard and clicking <b>Generate API token</b>.',
//     ],

//     // Example requests for each endpoint will be shown in each of these languages.
//     // Supported options are: bash, javascript, php, python
//     // To add a language of your own, see https://scribe.knuckles.wtf/laravel/advanced/example-requests
//     // Note: does not work for `external` docs types
//     'example_languages' => [
//         'bash',
//         'javascript',
//     ],

//     // Generate a Postman collection (v2.1.0) in addition to HTML docs.
//     // For 'static' docs, the collection will be generated to public/docs/collection.json.
//     // For 'laravel' docs, it will be generated to storage/app/scribe/collection.json.
//     // Setting `laravel.add_routes` to true (above) will also add a route for the collection.
//     'postman' => [
//         'enabled' => true,

//         'overrides' => [
//             // 'info.version' => '2.0.0',
//         ],
//     ],

//     // Generate an OpenAPI spec in addition to docs webpage.
//     // For 'static' docs, the collection will be generated to public/docs/openapi.yaml.
//     // For 'laravel' docs, it will be generated to storage/app/scribe/openapi.yaml.
//     // Setting `laravel.add_routes` to true (above) will also add a route for the spec.
//     'openapi' => [
//         'enabled' => true,

//         // The OpenAPI spec version to generate. Supported versions: '3.0.3', '3.1.0'.
//         // OpenAPI 3.1 is more compatible with JSON Schema and is becoming the dominant version.
//         // See https://spec.openapis.org/oas/v3.1.0 for details on 3.1 changes.
//         'version' => '3.0.3',

//         'overrides' => [
//             // 'info.version' => '2.0.0',
//         ],

//         // Additional generators to use when generating the OpenAPI spec.
//         // Should extend `Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator`.
//         'generators' => [],
//     ],

//     'groups' => [
//         // Endpoints which don't have a @group will be placed in this default group.
//         'default' => 'Endpoints',

//         // By default, Scribe will sort groups alphabetically, and endpoints in the order their routes are defined.
//         // You can override this by listing the groups, subgroups and endpoints here in the order you want them.
//         // See https://scribe.knuckles.wtf/blog/laravel-v4#easier-sorting and https://scribe.knuckles.wtf/laravel/reference/config#order for details
//         // Note: does not work for `external` docs types
//         'order' => [],
//     ],

//     // Custom logo path. This will be used as the value of the src attribute for the <img> tag,
//     // so make sure it points to an accessible URL or path. Set to false to not use a logo.
//     // For example, if your logo is in public/img:
//     // - 'logo' => '../img/logo.png' // for `static` type (output folder is public/docs)
//     // - 'logo' => 'img/logo.png' // for `laravel` type
//     'logo' => false,

//     // Customize the "Last updated" value displayed in the docs by specifying tokens and formats.
//     // Examples:
//     // - {date:F j Y} => March 28, 2022
//     // - {git:short} => Short hash of the last Git commit
//     // Available tokens are `{date:<format>}` and `{git:<format>}`.
//     // The format you pass to `date` will be passed to PHP's `date()` function.
//     // The format you pass to `git` can be either "short" or "long".
//     // Note: does not work for `external` docs types
//     'last_updated' => '最后更新时间: {date:F j, Y}',

//     'examples' => [
//         // Set this to any number to generate the same example values for parameters on each run,
//         'faker_seed' => 1234,

//         // With API resources and transformers, Scribe tries to generate example models to use in your API responses.
//         // By default, Scribe will try the model's factory, and if that fails, try fetching the first from the database.
//         // You can reorder or remove strategies here.
//         'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
//     ],

//     // The strategies Scribe will use to extract information about your routes at each stage.
//     // Use configureStrategy() to specify settings for a strategy in the list.
//     // Use removeStrategies() to remove an included strategy.
//     'strategies' => [
//         'metadata' => [
//             ...Defaults::METADATA_STRATEGIES,
//         ],
//         'headers' => [
//             ...Defaults::HEADERS_STRATEGIES,
//             Strategies\StaticData::withSettings(data: [
//                 'Content-Type' => 'application/json',
//                 'Accept' => 'application/json',
//             ]),
//         ],
//         'urlParameters' => [
//             ...Defaults::URL_PARAMETERS_STRATEGIES,
//         ],
//         'queryParameters' => [
//             ...Defaults::QUERY_PARAMETERS_STRATEGIES,
//         ],
//         'bodyParameters' => [
//             ...Defaults::BODY_PARAMETERS_STRATEGIES,
//         ],
//         'responses' => configureStrategy(
//             Defaults::RESPONSES_STRATEGIES,
//             Strategies\Responses\ResponseCalls::withSettings(
//                 only: ['GET *'],
//                 // Recommended: disable debug mode in response calls to avoid error stack traces in responses
//                 config: [
//                     'app.debug' => false,
//                 ]
//             )
//         ),
//         'responseFields' => [
//             ...Defaults::RESPONSE_FIELDS_STRATEGIES,
//         ]
//     ],

//     // For response calls, API resource responses and transformer responses,
//     // Scribe will try to start database transactions, so no changes are persisted to your database.
//     // Tell Scribe which connections should be transacted here. If you only use one db connection, you can leave this as is.
//     'database_connections_to_transact' => [config('database.default')],

//     'fractal' => [
//         // If you are using a custom serializer with league/fractal, you can specify it here.
//         'serializer' => null,
//     ],
// ];
