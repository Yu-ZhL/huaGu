<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeimaoService;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Response;

#[Group("飞猫选品", "第三方选品平台接口")]
class FeimaoProductController extends Controller
{
    protected $feimaoService;

    public function __construct(FeimaoService $feimaoService)
    {
        $this->feimaoService = $feimaoService;
    }

    /**
     * 批量查询商品详情
     */
    #[BodyParam("channel", "string", "渠道 (调试用，固定为temu)", required: false, example: "temu")]
    #[BodyParam("productIds", "array", "商品ID数组", required: true, example: ["601100055961140", "601099661205317", "601105314564080"])]
    #[BodyParam("pageNum", "integer", "页码", required: false, example: 1)]
    #[BodyParam("pageSize", "integer", "每页数量", required: false, example: 40)]
    #[Response([
        "code" => 200,
        "message" => "success",
        "data" => [
            "list" => [
                [
                    "productId" => "601104442920701",
                    "productName" => "示例商品",
                    "price" => 100
                ]
            ]
        ]
    ])]
    public function index(Request $request)
    {
        $validated = $request->validate([
            'productIds' => 'required|array',
            'pageNum' => 'integer',
            'pageSize' => 'integer',
        ]);

        $productIds = $validated['productIds'];
        $pageNum = $request->input('pageNum', 1);
        $pageSize = $request->input('pageSize', 40);

        try {
            $result = $this->feimaoService->getProductList($productIds, $pageNum, $pageSize);
            return response()->json($result);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // 捕获超时错误，返回友好的提示信息
            if (strpos($message, 'timed out') !== false || strpos($message, 'cURL error 28') !== false) {
                return response()->json([
                    'code' => 504,
                    'message' => '请求第三方接口超时，请稍后重试'
                ], 504);
            }

            return response()->json([
                'code' => 500,
                'message' => '接口报错: ' . $message
            ], 500);
        }
    }
    /**
     * 获取分类列表
     * 
     * 获取飞猫选品的商品分类。parentId为0时获取一级分类，传入一级分类ID时获取二级分类。
     */
    #[BodyParam("parentId", "integer", "父级ID (0为一级分类)", required: false, example: 0)]
    #[Response([
        "code" => 200,
        "message" => "Success",
        "data" => [
            [
                "categoryId" => 953224,
                "parentId" => 0,
                "categoryLevel" => 1,
                "categoryName" => "珠宝配饰及衍生品"
            ],
            [
                "categoryId" => 951432,
                "parentId" => 0,
                "categoryLevel" => 1,
                "categoryName" => "收藏品"
            ]
        ]
    ], 200, "一级分类响应")]
    #[Response([
        "code" => 200,
        "message" => "Success",
        "data" => [
            [
                "categoryId" => 964744,
                "parentId" => 953224,
                "categoryLevel" => 2,
                "categoryName" => "梅利特"
            ],
            [
                "categoryId" => 964616,
                "parentId" => 953224,
                "categoryLevel" => 2,
                "categoryName" => "琥珀色"
            ]
        ]
    ], 200, "二级分类响应")]
    public function getCategories(Request $request)
    {
        $parentId = $request->input('parentId', 0);
        $result = $this->feimaoService->getCategories($parentId);
        return response()->json($result);
    }

    /**
     * 获取分类商品列表
     * 
     * 获取飞猫选品指定分类下的商品列表，支持分页。
     */
    #[BodyParam("pageNum", "integer", "页码", required: false, example: 1)]
    #[BodyParam("pageSize", "integer", "每页数量", required: false, example: 10)]
    #[BodyParam("categoryId", "string", "分类ID", required: false, example: "953224")]
    #[Response([
        "code" => 200,
        "message" => "Success",
        "data" => [
            "current" => 1,
            "pages" => 40206,
            "records" => [
                [
                    "id" => 1,
                    "productId" => "1732140290455343698",
                    "categoryId" => "601152",
                    "categoryName" => "女装和内衣",
                    "commissionRate" => 14.5,
                    "commissionRateText" => "1450",
                    "coverUrl" => "https://p16-oec-general-useast5.ttcdn-us.com/tos-useast5-i-omjb5zjo8w-tx/b84981e47a204a648979cff29cffb13f~tplv-fhlh96nyum-resize-jpeg:800:800.jpeg",
                    "title" => "Women's 2 Piece Matching Lounge Set",
                    "sales" => 845,
                    "salesText" => "845 sold",
                    "formatPrice" => 16.11,
                    "formatPriceText" => "$16.11",
                    "productLink" => "https://www.tiktok.com/shop/pdp/1732140290455343698",
                    "shopName" => "HHFSHOP"
                ]
            ],
            "size" => 10,
            "total" => 402058
        ],
        "timestamp" => 1769668251877
    ])]
    public function getCategoryProducts(Request $request)
    {
        $pageNum = $request->input('pageNum', 1);
        $pageSize = $request->input('pageSize', 10);
        $categoryId = $request->input('categoryId', '');

        $result = $this->feimaoService->getProductListByCategory($pageNum, $pageSize, $categoryId);
        return response()->json($result);
    }

    /**
     * 获取销量记录
     * 
     * 获取指定商品的销量历史记录。
     */
    #[BodyParam("productId", "string", "商品ID", required: true, example: "1732140290455343698")]
    #[Response([
        "code" => 200,
        "message" => "Success",
        "data" => [
            [
                "sales" => 845,
                "createdTime" => "2026-01-24 15:28:40"
            ],
            [
                "sales" => 845,
                "createdTime" => "2026-01-25 00:01:01"
            ]
        ],
        "timestamp" => 1769668346840
    ])]
    public function getSalesRecord(Request $request)
    {
        $request->validate([
            'productId' => 'required|string'
        ]);

        $productId = $request->input('productId');
        $result = $this->feimaoService->getSalesRecord($productId);
        return response()->json($result);
    }
}
