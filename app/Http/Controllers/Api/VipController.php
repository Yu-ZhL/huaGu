<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VipPlan;
use App\Models\Order;
use App\Models\UserAiPoint;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * VIP管理接口
 * 
 * @group VIP管理 (VIP Plans)
 */
class VipController extends Controller
{
    /**
     * 获取VIP套餐列表
     * 
     * 获取所有可用的VIP套餐，按推荐和排序展示
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "月度会员 68元",
     *       "description": "每月ai点数6000 可使用运费测算功能 约3000次使用额度",
     *       "price": "68.00",
     *       "final_price": "68.00",
     *       "ai_points": 6000,
     *       "duration_days": 30,
     *       "features": ["运费测算", "6000点数"],
     *       "is_recommended": false
     *     }
     *   ],
     *   "message": "获取成功"
     * }
     */
    public function index(): JsonResponse
    {
        $plans = VipPlan::active()->ordered()->get();

        return ApiResponse::success($plans, '获取成功');
    }

    /**
     * 获取我的VIP信息
     * 
     * 获取当前用户的VIP状态、AI点数余额等信息
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "is_vip": true,
     *     "vip_plan": {
     *       "id": 2,
     *       "name": "月度会员 128元"
     *     },
     *     "vip_expired_at": "2026-02-26 15:00:00",
     *     "ai_points": 15000,
     *     "ai_points_records_count": 5
     *   },
     *   "message": "获取成功"
     * }
     */
    public function myVipInfo(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'is_vip' => $user->isVip(),
            'vip_plan' => $user->vipPlan,
            'vip_expired_at' => $user->vip_expired_at?->toDateTimeString(),
            'ai_points' => $user->ai_points,
            'ai_points_records_count' => $user->aiPointRecords()->count(),
        ];

        return ApiResponse::success($data, '获取成功');
    }

    /**
     * 获取我的订单列表
     * 
     * 获取当前用户的VIP购买订单历史
     * 
     * @authenticated
     * 
     * @queryParam page integer 页码 Example: 1
     * @queryParam per_page integer 每页数量 Example: 10
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "order_no": "202601261500001ABC",
     *         "plan_name": "月度会员 128元",
     *         "final_price": "128.00",
     *         "status": "paid",
     *         "paid_at": "2026-01-26 15:05:00",
     *         "created_at": "2026-01-26 15:00:00"
     *       }
     *     ],
     *     "per_page": 10,
     *     "total": 1
     *   },
     *   "message": "获取成功"
     * }
     */
    public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);

        $orders = $user->orders()
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success($orders, '获取成功');
    }

    /**
     * 获取我的AI点数明细
     * 
     * 获取当前用户的AI点数变动记录
     * 
     * @authenticated
     * 
     * @queryParam page integer 页码 Example: 1
     * @queryParam per_page integer 每页数量 Example: 20
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "points": 15000,
     *         "balance_after": 15000,
     *         "type": "vip_purchase",
     *         "description": "购买VIP套餐",
     *         "expired_at": null,
     *         "created_at": "2026-01-26 15:05:00"
     *       }
     *     ],
     *     "per_page": 20,
     *     "total": 1
     *   },
     *   "message": "获取成功"
     * }
     */
    public function myAiPoints(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);

        $records = $user->aiPointRecords()
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success($records, '获取成功');
    }
}
