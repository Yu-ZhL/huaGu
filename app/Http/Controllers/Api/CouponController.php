<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 优惠码接口
 *
 * @group 优惠码管理 (Coupons)
 */
class CouponController extends Controller
{
    /**
     * 验证优惠码
     *
     * 验证优惠码是否可用，并返回折扣信息
     *
     * @authenticated
     *
     * @urlParam code string required 优惠码 Example: NEWUSER2026
     * @bodyParam amount number required 订单金额 Example: 128.00
     *
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "valid": true,
     *     "message": "优惠码可用",
     *     "coupon": {
     *       "code": "NEWUSER2026",
     *       "name": "新用户优惠",
     *       "type": "fixed",
     *       "discount_value": "10.00"
     *     },
     *     "discount_amount": "10.00",
     *     "final_amount": "118.00"
     *   },
     *   "message": "验证成功"
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "code": 400,
     *   "data": null,
     *   "message": "优惠码已过期"
     * }
     */
    // 验证优惠码
    public function check(Request $request, string $code): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ], [
            'amount.required' => '订单金额不能为空',
            'amount.numeric' => '订单金额格式错误',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return ApiResponse::error('优惠码不存在', 404);
        }

        $amount = floatval($request->amount);
        $validation = $coupon->isValid($amount);

        if (!$validation['valid']) {
            return ApiResponse::error($validation['message'], 400);
        }

        $discountAmount = $coupon->calculateDiscount($amount);
        $finalAmount = $amount - $discountAmount;

        return ApiResponse::success([
            'valid' => true,
            'message' => $validation['message'],
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'discount_value' => $coupon->discount_value,
            ],
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'final_amount' => number_format($finalAmount, 2, '.', ''),
        ], '验证成功');
    }
}
