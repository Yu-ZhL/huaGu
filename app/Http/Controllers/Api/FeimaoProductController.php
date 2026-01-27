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
    #[BodyParam("productIds", "array", "商品ID数组", required: true, example: ["601104442920701"])]
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
            return response()->json([
                'code' => 500,
                'message' => '接口报错: ' . $e->getMessage()
            ], 500);
        }
    }
}
