<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\VipPlan;
use App\Models\Coupon;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 订单管理接口
 * 
 * @group 订单管理 (Orders)
 */
class OrderController extends Controller
{
    /**
     * 创建订单
     * 
     * 选择VIP套餐创建订单，可选使用优惠码
     * 
     * @authenticated
     * 
     * @bodyParam vip_plan_id integer required VIP套餐ID Example: 2
     * @bodyParam coupon_code string 优惠码 Example: NEWUSER2026
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "id": 1,
     *     "order_no": "202601261500001ABC",
     *     "plan_name": "月度会员 128元",
     *     "original_price": "128.00",
     *     "discount_amount": "10.00",
     *     "final_price": "118.00",
     *     "status": "pending",
     *     "expired_at": "2026-01-26 15:30:00"
     *   },
     *   "message": "订单创建成功"
     * }
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vip_plan_id' => 'required|exists:vip_plans,id',
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ], [
            'vip_plan_id.required' => '请选择VIP套餐',
            'vip_plan_id.exists' => 'VIP套餐不存在',
            'coupon_code.exists' => '优惠码不存在',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $user = $request->user();
        $vipPlan = VipPlan::findOrFail($request->vip_plan_id);

        if (!$vipPlan->status) {
            return ApiResponse::error('该套餐已下架', 400);
        }

        $originalPrice = $vipPlan->final_price;
        $discountAmount = 0;
        $couponId = null;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();

            $priceFloat = floatval($originalPrice);
            $validation = $coupon->isValid($priceFloat);
            if (!$validation['valid']) {
                return ApiResponse::error($validation['message'], 400);
            }

            $discountAmount = $coupon->calculateDiscount($priceFloat);
            $couponId = $coupon->id;
        }

        $finalPrice = $originalPrice - $discountAmount;

        $order = Order::create([
            'user_id' => $user->id,
            'vip_plan_id' => $vipPlan->id,
            'coupon_id' => $couponId,
            'plan_name' => $vipPlan->name,
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'payment_method' => 'alipay',
            'status' => Order::STATUS_PENDING,
        ]);

        return ApiResponse::success($order, '订单创建成功');
    }

    /**
     * 查询订单详情
     * 
     * 根据订单号查询订单详细信息
     * 
     * @authenticated
     * 
     * @urlParam orderNo string required 订单号 Example: 202601261500001ABC
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "id": 1,
     *     "order_no": "202601261500001ABC",
     *     "plan_name": "月度会员 128元",
     *     "original_price": "128.00",
     *     "discount_amount": "10.00",
     *     "final_price": "118.00",
     *     "payment_method": "alipay",
     *     "status": "pending",
     *     "paid_at": null,
     *     "expired_at": "2026-01-26 15:30:00",
     *     "created_at": "2026-01-26 15:00:00"
     *   },
     *   "message": "获取成功"
     * }
     */
    public function show(Request $request, string $orderNo): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return ApiResponse::error('订单不存在', 404);
        }

        return ApiResponse::success($order, '获取成功');
    }

    /**
     * 查询订单支付状态
     * 
     * 查询订单当前的支付状态
     * 
     * @authenticated
     * 
     * @urlParam orderNo string required 订单号 Example: 202601261500001ABC
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "order_no": "202601261500001ABC",
     *     "status": "paid",
     *     "paid_at": "2026-01-26 15:05:00"
     *   },
     *   "message": "查询成功"
     * }
     */
    public function queryPaymentStatus(Request $request, string $orderNo): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return ApiResponse::error('订单不存在', 404);
        }

        return ApiResponse::success([
            'order_no' => $order->order_no,
            'status' => $order->status,
            'paid_at' => $order->paid_at?->toDateTimeString(),
        ], '查询成功');
    }

    /**
     * 取消订单
     * 
     * 取消未支付的订单
     * 
     * @authenticated
     * 
     * @urlParam orderNo string required 订单号 Example: 202601261500001ABC
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": null,
     *   "message": "订单已取消"
     * }
     */
    public function cancel(Request $request, string $orderNo): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return ApiResponse::error('订单不存在', 404);
        }

        if (!$order->isPending()) {
            return ApiResponse::error('该订单无法取消', 400);
        }

        $order->cancel();

        return ApiResponse::success(null, '订单已取消');
    }
}
