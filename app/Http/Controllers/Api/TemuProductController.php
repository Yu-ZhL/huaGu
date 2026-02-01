<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemuCollectedProduct;
use App\Models\Product1688Source;
use App\Models\SiteSetting;
use App\Models\UserAiPoint;
use App\Services\TemuCollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\Authenticated;

#[Group("Temu 商品管理", "Temu 商品采集和同款管理")]
#[Authenticated]
class TemuProductController extends Controller
{
    protected $temuService;

    public function __construct(TemuCollectionService $temuService)
    {
        $this->temuService = $temuService;
    }

    /**
     * 获取采集到的temu商品列表 - 给插件用的
     */
    #[QueryParam("page", "integer", "页码", required: false, example: 1)]
    #[QueryParam("per_page", "integer", "每页数量", required: false, example: 10)]
    #[QueryParam("product_ids", "string", "针对性返回当前页面商品状态 (逗号分隔)", required: false, example: "601105,601106")]
    #[Response([
        "success" => true,
        "data" => [
            "current_page" => 1,
            "data" => [
                [
                    "id" => 1,
                    "product_id" => "601099661205317",
                    "title" => "示例商品标题",
                    "sale_price" => 99.99,
                    "product_data" => ["...全量数据"],
                    "sources_count" => 5,
                    "collected_at" => "2026-02-01 17:00:00"
                ]
            ]
        ]
    ])]
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = TemuCollectedProduct::where('user_id', $user->id);

        // 插件侧：针对性请求当前页面商品的采集状态
        if ($request->filled('product_ids')) {
            $ids = explode(',', $request->input('product_ids'));
            $query->whereIn('product_id', $ids);

            // 针对性请求时默认给大一点，确保全返回
            $perPage = $request->input('per_page', 100);
        } else {
            $perPage = $request->input('per_page', 10);
        }

        $products = $query->with('sources1688')
            ->withCount('sources1688')
            ->orderBy('collected_at', 'desc')
            ->paginate($perPage);

        // 映射字段名以保持向后兼容
        $products->getCollection()->transform(function ($product) {
            $product->sources_count = $product->sources1688_count;
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * 获取用户爬取的temu商品列表
     */
    #[QueryParam("page", "integer", "页码", required: false, example: 1)]
    #[QueryParam("per_page", "integer", "每页数量", required: false, example: 15)]
    #[QueryParam("title", "string", "模糊搜索标题", required: false, example: "充电器")]
    #[QueryParam("remark", "string", "模糊搜索备注", required: false, example: "待处理")]
    #[QueryParam("shop_name", "string", "模糊搜索名店", required: false, example: "名店")]
    #[QueryParam("reviews_min", "integer", "最小评价数", required: false, example: 100)]
    #[QueryParam("sales_min", "integer", "最小销量", required: false, example: 1000)]
    #[QueryParam("price_min", "number", "最小价格", required: false, example: 5.0)]
    #[QueryParam("is_brand", "integer", "是否品牌 (1:是, 0:否)", required: false, example: 1)]
    #[Response([
        "success" => true,
        "data" => [
            "current_page" => 1,
            "data" => [
                [
                    "id" => 1,
                    "product_id" => "601099661205317",
                    "title" => "精简版示例",
                    "sale_price" => 99.99,
                    "shop_name" => "示例店铺",
                    "sales" => 500,
                    "rating" => 4.5,
                    "cover_image" => "http://example.com/img.jpg"
                ]
            ]
        ]
    ])]
    public function indexSimple(Request $request)
    {
        $user = auth()->user();

        $query = TemuCollectedProduct::where('user_id', $user->id);

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->input('title') . '%');
        }

        if ($request->filled('remark')) {
            $query->where('remark', 'like', '%' . $request->input('remark') . '%');
        }

        if ($request->filled('shop_name')) {
            $query->where('shop_name', 'like', '%' . $request->input('shop_name') . '%');
        }

        if ($request->filled('reviews_min')) {
            $query->where('reviews', '>=', $request->input('reviews_min'));
        }

        if ($request->filled('sales_min')) {
            $query->where('sales', '>=', $request->input('sales_min'));
        }

        if ($request->filled('price_min')) {
            $query->where('sale_price', '>=', $request->input('price_min'));
        }

        if ($request->filled('is_brand')) {
            $query->where('is_brand', $request->input('is_brand'));
        }

        $products = $query->select([
            'id',
            'user_id',
            'product_id',
            'site_url',
            'platform',
            'title',
            'sale_price',
            'weight',
            'brand',
            'cover_image',
            'collected_at',
            'remark',
            'sales',
            'reviews',
            'rating',
            'freight',
            'profit',
            'source_price_1688',
            'is_brand',
            'shop_name'
        ])
            ->withCount('sources1688')
            ->orderBy('collected_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * 获取商品详情 - 给插件用的
     */
    #[Response([
        "success" => true,
        "data" => [
            "id" => 1,
            "product_id" => "601099661205317",
            "title" => "示例商品",
            "sale_price" => 99.99,
            "weight" => 0.5,
            "sources" => []
        ]
    ])]
    public function show($id)
    {
        $user = auth()->user();

        $product = TemuCollectedProduct::with('sources1688')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * 开始采集1688同款 - 给插件用的
     */
    #[BodyParam("product_id", "string", "Temu商品ID (支持数据库ID或Temu原只读ID)", required: true, example: "6011xxx")]
    #[BodyParam("search_method", "string", "搜索方式 (image/url)", required: false, example: "image")]
    #[BodyParam("max_count", "integer", "最大采集数量", required: false, example: 20)]
    #[Response([
        "success" => true,
        "message" => "成功采集 10 条1688同款",
        "data" => [
            "count" => 10,
            "total" => 10
        ]
    ])]
    public function collectSimilar(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required',
            'search_method' => 'string|in:image,url',
            'max_count' => 'integer|min:1|max:20',
        ]);

        $user = auth()->user();
        $productId = $validated['product_id'];
        $searchMethod = $validated['search_method'] ?? 'image';
        $maxCount = $validated['max_count'] ?? 20;

        // 获取AI点数配置
        $pointsCost = SiteSetting::getAiPoints1688Search();

        // 检查点数是否足够
        if ($user->ai_points < $pointsCost) {
            return response()->json([
                'success' => false,
                'code' => 400,
                'message' => 'AI点数不足,当前点数:' . $user->ai_points . ',需要:' . $pointsCost,
            ], 400);
        }

        // 修正：支持接收前端显式传来的图片链接
        $imgUrl = $request->input('img_url');

        $result = $this->temuService->collectSimilarProducts(
            $productId,
            $user->id,
            $searchMethod,
            $maxCount,
            $imgUrl
        );

        // 扣除点数(仅在成功时)
        if ($result['success'] ?? false) {
            UserAiPoint::deductPoints(
                $user->id,
                $pointsCost,
                UserAiPoint::TYPE_CONSUME,
                '1688货源采集',
                isset($result['product_id']) && is_numeric($result['product_id']) ? (int) $result['product_id'] : null
            );

            Log::info('1688货源采集消耗AI点数', [
                'user_id' => $user->id,
                'product_id' => $productId,
                'points' => $pointsCost,
                'remaining' => $user->fresh()->ai_points
            ]);

            $result['ai_points_used'] = $pointsCost;
        }

        return response()->json($result);
    }

    /**
     * 获取商品的1688货源列表
     */
    #[Response([
        "success" => true,
        "data" => [
            [
                "id" => 1,
                "title" => "1688商品标题",
                "price" => 29.90,
                "image" => "https://cbu01.alicdn.com/img/xxx.jpg",
                "url" => "https://detail.1688.com/offer/12345.html",
                "is_primary" => true,
                "tags" => ["7天无理由", "48小时发货"]
            ]
        ]
    ])]
    public function getSources($productId)
    {
        $user = auth()->user();

        $product = TemuCollectedProduct::where('id', $productId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $sources = Product1688Source::where('temu_product_id', $productId)
            ->where('user_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sources,
        ]);
    }

    /**
     * 设置主选货源
     */
    #[BodyParam("source_id", "integer", "1688货源ID", required: true, example: 1)]
    #[BodyParam("product_id", "integer", "Temu商品ID", required: true, example: 1)]
    #[Response([
        "success" => true,
        "message" => "设置成功",
        "data" => [
            "id" => 1,
            "is_primary" => true
        ]
    ])]
    public function setPrimarySource(Request $request)
    {
        $validated = $request->validate([
            'source_id' => 'required|integer',
            'product_id' => 'required|integer',
        ]);

        $user = auth()->user();

        try {
            $source = $this->temuService->setPrimarySource(
                $validated['source_id'],
                $validated['product_id'],
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => '设置成功',
                'data' => $source,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 计算商品利润 - 缺少[重量] 接口暂不可用
     */
    #[BodyParam("product_id", "integer", "Temu商品ID", required: true, example: 1)]
    #[Response([
        "success" => true,
        "data" => [
            "sale_price" => 99.99,
            "purchase_price" => 29.90,
            "weight" => 0.5,
            "freight" => 59.50,
            "platform_fee" => 5.00,
            "profit" => 4.59,
            "freight_config" => [
                "freight_price_per_kg" => 85.00,
                "operation_fee" => 17.00
            ]
        ]
    ])]
    public function calculateProfit(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
        ]);

        $user = auth()->user();

        // 预留功能:检查点数(暂不启用)
        // $pointsCost = SiteSetting::getAiPointsProfitCalc();
        // if ($user->ai_points < $pointsCost) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'AI点数不足,当前点数:' . $user->ai_points . ',需要:' . $pointsCost,
        //     ], 400);
        // }

        try {
            $result = $this->temuService->calculateProductProfit(
                $validated['product_id'],
                $user->id
            );

            // 预留功能:扣除点数(暂不启用)
            // if ($result['success']) {
            //     UserAiPoint::deductPoints(
            //         $user->id,
            //         $pointsCost,
            //         UserAiPoint::TYPE_CONSUME,
            //         'AI计算利润',
            //         $validated['product_id']
            //     );
            //
            //     Log::info('AI计算利润消耗点数', [
            //         'user_id' => $user->id,
            //         'product_id' => $validated['product_id'],
            //         'points' => $pointsCost,
            //         'remaining' => $user->fresh()->ai_points
            //     ]);
            //
            //     $result['ai_points_used'] = $pointsCost;
            // }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 修改商品备注
     */
    #[BodyParam("remark", "string", "备注内容", required: false, example: "重要商品，需加急处理")]
    #[Response([
        "success" => true,
        "message" => "备注已更新",
        "data" => [
            "id" => 1,
            "remark" => "重要商品"
        ]
    ])]
    public function updateRemark(Request $request, $id)
    {
        $validated = $request->validate([
            'remark' => 'nullable|string',
        ]);

        $user = auth()->user();
        $product = TemuCollectedProduct::where('user_id', $user->id)->findOrFail($id);

        $product->update([
            'remark' => $validated['remark']
        ]);

        return response()->json([
            'success' => true,
            'message' => '备注已更新',
            'data' => $product
        ]);
    }

    /**
     * 删除采集商品
     *
     * 删除指定的 Temu 采集商品，并级联删除其名下所有 1688 同款货源数据。
     */
    #[Response([
        "success" => true,
        "message" => "商品已删除"
    ])]
    public function destroy($id)
    {
        $user = auth()->user();
        $product = TemuCollectedProduct::where('user_id', $user->id)->findOrFail($id);

        // 数据库已有 ON DELETE CASCADE，关联的 1688 货源会自动删除
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => '商品已删除',
        ]);
    }
}
