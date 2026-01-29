<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserAiPoint;
use App\Models\SiteSetting;
use App\Http\Services\AlipayService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 支付接口
 * 
 * @group 支付接口
 */
class PaymentController extends Controller
{
    protected $alipayService;

    public function __construct(AlipayService $alipayService)
    {
        $this->alipayService = $alipayService;
    }

    /**
     * 开通VIP会员（创建支付宝支付）
     *
     * 为已创建的订单生成支付宝支付二维码，用户扫码后完成支付即可开通VIP
     *
     * @authenticated
     * 
     * @bodyParam order_no string required 订单号 Example: 202601261500001ABC
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "qr_code": "https://qr.alipay.com/..."
     *   },
     *   "message": "支付创建成功，请扫描二维码完成支付"
     * }
     */
    public function createAlipay(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_no' => 'required|string',
        ], [
            'order_no.required' => '订单号不能为空',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $user = $request->user();
        $order = Order::where('order_no', $request->order_no)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return ApiResponse::error('订单不存在', 400);
        }

        if ($order->isPaid()) {
            return ApiResponse::error('订单已支付', 400);
        }

        if ($order->isExpired()) {
            return ApiResponse::error('订单已过期', 400);
        }

        try {
            $result = $this->alipayService->createFaceToFaceOrder([
                'order_no' => $order->order_no,
                'final_price' => $order->final_price,
                'plan_name' => $order->plan_name,
            ]);

            return ApiResponse::success([
                'order_no' => $order->order_no,
                'qr_code' => $result->qr_code,
            ], '支付二维码生成成功');
        } catch (\Exception $e) {
            Log::error('创建支付宝订单失败', [
                'order_no' => $order->order_no,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('支付创建失败，请稍后重试', 500);
        }
    }

    /**
     * 支付宝异步通知
     * 
     * 接收支付宝的支付结果通知
     */
    public function alipayNotify(Request $request)
    {
        Log::info('收到支付宝通知', $request->all());

        try {
            $notify = $this->alipayService->verifyNotify();

            $orderNo = $notify->out_trade_no;
            $tradeNo = $notify->trade_no;
            $tradeStatus = $notify->trade_status;

            if ($tradeStatus !== 'TRADE_SUCCESS') {
                Log::info('支付状态非成功', ['trade_status' => $tradeStatus]);
                return 'success';
            }

            $order = Order::where('order_no', $orderNo)->first();

            if (!$order) {
                Log::error('订单不存在', ['order_no' => $orderNo]);
                return 'fail';
            }

            if ($order->isPaid()) {
                Log::info('订单已支付，跳过', ['order_no' => $orderNo]);
                return 'success';
            }

            DB::transaction(function () use ($order, $tradeNo) {
                $order->markAsPaid($tradeNo);

                $user = $order->user;
                $vipPlan = $order->vipPlan;

                $newExpiredAt = now()->addDays($vipPlan->duration_days);
                if ($user->vip_expired_at && $user->vip_expired_at->isFuture()) {
                    $newExpiredAt = $user->vip_expired_at->addDays($vipPlan->duration_days);
                }

                $user->update([
                    'vip_level_id' => $vipPlan->id,
                    'vip_expired_at' => $newExpiredAt,
                ]);

                UserAiPoint::addPoints(
                    $user->id,
                    $vipPlan->ai_points,
                    UserAiPoint::TYPE_VIP_PURCHASE,
                    "购买VIP套餐：{$vipPlan->name}",
                    $order->id
                );

                if ($order->coupon_id) {
                    $order->coupon->incrementUsedCount();
                }
            });

            Log::info('订单支付成功处理完成', ['order_no' => $orderNo]);

            return 'success';
        } catch (\Exception $e) {
            Log::error('处理支付宝通知失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'fail';
        }
    }
}
