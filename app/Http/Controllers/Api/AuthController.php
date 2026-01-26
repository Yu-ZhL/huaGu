<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsCode;
use App\Models\User;
use App\Services\IpLocationService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * 用户认证接口
 * 
 * @group 用户认证
 */
class AuthController extends Controller
{
    private $ipLocationService;

    public function __construct(IpLocationService $ipLocationService)
    {
        $this->ipLocationService = $ipLocationService;
    }

    /**
     * 发送短信验证码
     * 
     * 向指定手机号发送验证码，验证码有效期5分钟，60秒内不可重复发送
     * 
     * @bodyParam phone_area_code string 国际区号，默认86 Example: 86
     * @bodyParam phone string required 手机号 Example: 13800138000
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": null,
     *   "message": "验证码已发送，请注意查收"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "code": 400,
     *   "data": null,
     *   "message": "请等待60秒后再试"
     * }
     */
    public function sendCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_area_code' => 'string|max:10',
            'phone' => 'required|string|max:20',
        ], [
            'phone.required' => '手机号不能为空',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $phoneAreaCode = $request->input('phone_area_code', '86');
        $phone = $request->input('phone');

        // 检查60秒内是否已发送
        $recentCode = SmsCode::where('phone_area_code', $phoneAreaCode)
            ->where('phone', $phone)
            ->where('created_at', '>', now()->subSeconds(60))
            ->first();

        if ($recentCode) {
            return ApiResponse::error('请等待60秒后再试', 400);
        }

        // 生成验证码（暂时固定）
        $code = '123456';

        SmsCode::create([
            'phone_area_code' => $phoneAreaCode,
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
            'ip' => $request->ip(),
        ]);

        return ApiResponse::success(null, '验证码已发送，请注意查收');
    }

    /**
     * 用户注册
     * 
     * 通过手机号和短信验证码注册新用户，注册成功后自动登录
     * 
     * @bodyParam phone_area_code string 国际区号，默认86 Example: 86
     * @bodyParam phone string required 手机号 Example: 13800138000
     * @bodyParam sms_code string required 短信验证码 Example: 123456
     * @bodyParam password string required 密码，至少6位 Example: password123
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "phone": "13800138000",
     *       "phone_area_code": "86",
     *       "name": null,
     *       "vip_level_id": 0
     *     },
     *     "token": "1|abc...xyz"
     *   },
     *   "message": "注册成功"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "code": 400,
     *   "data": null,
     *   "message": "该手机号已注册"
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_area_code' => 'string|max:10',
            'phone' => 'required|string|max:20',
            'sms_code' => 'required|string',
            'password' => 'required|string|min:6',
        ], [
            'phone.required' => '手机号不能为空',
            'sms_code.required' => '验证码不能为空',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少6位',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $phoneAreaCode = $request->input('phone_area_code', '86');
        $phone = $request->input('phone');
        $smsCode = $request->input('sms_code');
        $password = $request->input('password');

        // 检查手机号是否已注册
        $exists = User::where('phone_area_code', $phoneAreaCode)
            ->where('phone', $phone)
            ->exists();

        if ($exists) {
            return ApiResponse::error('该手机号已注册', 400);
        }

        // 检查IP注册限制
        $ip = $request->ip();
        $maxPerIp = \App\Models\SiteSetting::getMaxRegisterPerIp();

        if ($maxPerIp > 0) {
            $todayCount = User::where('register_ip', $ip)
                ->whereDate('created_at', today())
                ->count();

            if ($todayCount >= $maxPerIp) {
                return ApiResponse::error('当前IP注册频繁，请明日再试', 429);
            }
        }

        // 验证短信验证码
        $code = SmsCode::where('phone_area_code', $phoneAreaCode)
            ->where('phone', $phone)
            ->where('code', $smsCode)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$code) {
            return ApiResponse::error('验证码错误或已过期', 400);
        }

        $code->markAsUsed();

        $ip = $request->ip();
        $location = $this->ipLocationService->getLocation($ip);

        $user = User::create([
            'phone_area_code' => $phoneAreaCode,
            'phone' => $phone,
            'password' => Hash::make($password),
            'plaintext_password' => $password,
            'register_ip' => $ip,
            'register_location' => $location,
            'last_login_ip' => $ip,
            'last_login_location' => $location,
            'last_login_at' => now(),
        ]);

        $giftPoints = \App\Models\SiteSetting::getNewUserGiftPoints();
        $expireDays = \App\Models\SiteSetting::getGiftPointsExpireDays();

        if ($giftPoints > 0) {
            $user->addAiPoints(
                $giftPoints,
                \App\Models\UserAiPoint::TYPE_REGISTER_GIFT,
                '新用户注册赠送',
                null,
                now()->addDays($expireDays)
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'phone_area_code' => $user->phone_area_code,
                'name' => $user->name,
                'vip_level_id' => $user->vip_level_id,
                'ai_points' => $user->ai_points,
            ],
            'token' => $token,
        ], '注册成功');
    }

    /**
     * 用户登录
     * 
     * 通过手机号和密码登录，返回访问令牌
     * 
     * @bodyParam phone_area_code string 国际区号，默认86 Example: 86
     * @bodyParam phone string required 手机号 Example: 13800138000
     * @bodyParam password string required 密码 Example: password123
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "phone": "13800138000",
     *       "phone_area_code": "86",
     *       "name": null,
     *       "vip_level_id": 0
     *     },
     *     "token": "2|xyz...abc"
     *   },
     *   "message": "登录成功"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "code": 401,
     *   "data": null,
     *   "message": "手机号或密码错误"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_area_code' => 'string|max:10',
            'phone' => 'required|string|max:20',
            'password' => 'required|string',
        ], [
            'phone.required' => '手机号不能为空',
            'password.required' => '密码不能为空',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $phoneAreaCode = $request->input('phone_area_code', '86');
        $phone = $request->input('phone');
        $password = $request->input('password');

        $user = User::where('phone_area_code', $phoneAreaCode)
            ->where('phone', $phone)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return ApiResponse::error('手机号或密码错误', 401);
        }

        if ($user->status == 0) {
            return ApiResponse::error('账号已被禁用', 403);
        }

        $ip = $request->ip();
        $location = $this->ipLocationService->getLocation($ip);

        $user->update([
            'last_login_ip' => $ip,
            'last_login_location' => $location,
            'last_login_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'phone_area_code' => $user->phone_area_code,
                'name' => $user->name,
                'vip_level_id' => $user->vip_level_id,
            ],
            'token' => $token,
        ], '登录成功');
    }

    /**
     * 重置密码
     * 
     * 通过短信验证码重置密码
     * 
     * @bodyParam phone_area_code string 国际区号，默认86 Example: 86
     * @bodyParam phone string required 手机号 Example: 13800138000
     * @bodyParam sms_code string required 短信验证码 Example: 123456
     * @bodyParam password string required 新密码，至少6位 Example: newpassword123
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": null,
     *   "message": "密码重置成功，请重新登录"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "code": 404,
     *   "data": null,
     *   "message": "该手机号未注册"
     * }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_area_code' => 'string|max:10',
            'phone' => 'required|string|max:20',
            'sms_code' => 'required|string',
            'password' => 'required|string|min:6',
        ], [
            'phone.required' => '手机号不能为空',
            'sms_code.required' => '验证码不能为空',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少6位',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $phoneAreaCode = $request->input('phone_area_code', '86');
        $phone = $request->input('phone');
        $smsCode = $request->input('sms_code');
        $password = $request->input('password');

        $user = User::where('phone_area_code', $phoneAreaCode)
            ->where('phone', $phone)
            ->first();

        if (!$user) {
            return ApiResponse::error('该手机号未注册', 404);
        }

        // 验证短信验证码
        $code = SmsCode::where('phone_area_code', $phoneAreaCode)
            ->where('phone', $phone)
            ->where('code', $smsCode)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$code) {
            return ApiResponse::error('验证码错误或已过期', 400);
        }

        $code->markAsUsed();

        $user->update([
            'password' => Hash::make($password),
            'plaintext_password' => $password,
        ]);

        // 清除所有token
        $user->tokens()->delete();

        return ApiResponse::success(null, '密码重置成功，请重新登录');
    }

    /**
     * 获取当前用户信息
     * 
     * 获取已登录用户的详细信息
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": {
     *     "id": 1,
     *     "phone": "13800138000",
     *     "phone_area_code": "86",
     *     "name": null,
     *     "email": null,
     *     "vip_level_id": 0,
     *     "vip_expired_at": null,
     *     "last_login_at": "2026-01-23 15:30:00",
     *     "last_login_location": "中国 广东省 深圳市"
     *   },
     *   "message": "获取成功"
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'id' => $user->id,
            'phone' => $user->phone,
            'phone_area_code' => $user->phone_area_code,
            'name' => $user->name,
            'email' => $user->email,
            'vip_level_id' => $user->vip_level_id,
            'vip_expired_at' => $user->vip_expired_at?->toDateTimeString(),
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'last_login_location' => $user->last_login_location,
        ], '获取成功');
    }

    /**
     * 退出登录
     * 
     * 删除当前访问令牌
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "code": 200,
     *   "data": null,
     *   "message": "退出成功"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, '退出成功');
    }
}
