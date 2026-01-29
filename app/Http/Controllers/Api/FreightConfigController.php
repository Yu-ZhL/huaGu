<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Carbon\Carbon;

#[Group("运费配置", "用户运费定价配置管理")]
#[Authenticated]
class FreightConfigController extends Controller
{
    /**
     * 获取运费配置
     */
    #[Response([
        "success" => true,
        "data" => [
            "freight_price_per_kg" => 85.00,
            "operation_fee" => 17.00,
            "freight_config_updated_at" => "2026-01-29 10:00:00",
            "can_update" => true,
            "next_update_time" => null
        ]
    ])]
    public function show()
    {
        $user = auth()->user();

        $canUpdate = true;
        $nextUpdateTime = null;

        if ($user->freight_config_updated_at) {
            $hoursSinceUpdate = Carbon::now()->diffInHours($user->freight_config_updated_at);
            if ($hoursSinceUpdate < 24) {
                $canUpdate = false;
                $nextUpdateTime = $user->freight_config_updated_at->addHours(24);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'freight_price_per_kg' => (float) $user->freight_price_per_kg,
                'operation_fee' => (float) $user->operation_fee,
                'freight_config_updated_at' => $user->freight_config_updated_at?->format('Y-m-d H:i:s'),
                'can_update' => $canUpdate,
                'next_update_time' => $nextUpdateTime?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * 更新运费配置
     */
    #[BodyParam("freight_price_per_kg", "number", "运费单价(元/kg)", required: true, example: 85.00)]
    #[BodyParam("operation_fee", "number", "操作费(元)", required: true, example: 17.00)]
    #[Response([
        "success" => true,
        "message" => "配置更新成功",
        "data" => [
            "freight_price_per_kg" => 85.00,
            "operation_fee" => 17.00
        ]
    ])]
    #[Response([
        "success" => false,
        "message" => "24小时内仅支持修改一次，下次可修改时间: 2026-01-30 10:00:00"
    ], 400)]
    public function update(Request $request)
    {
        $validated = $request->validate([
            'freight_price_per_kg' => 'required|numeric|min:0',
            'operation_fee' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();

        if ($user->freight_config_updated_at) {
            $hoursSinceUpdate = Carbon::now()->diffInHours($user->freight_config_updated_at);
            if ($hoursSinceUpdate < 24) {
                $nextUpdateTime = $user->freight_config_updated_at->addHours(24);
                return response()->json([
                    'success' => false,
                    'message' => "24小时内仅支持修改一次，下次可修改时间: {$nextUpdateTime->format('Y-m-d H:i:s')}",
                ], 400);
            }
        }

        $user->freight_price_per_kg = $validated['freight_price_per_kg'];
        $user->operation_fee = $validated['operation_fee'];
        $user->freight_config_updated_at = Carbon::now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => '配置更新成功',
            'data' => [
                'freight_price_per_kg' => (float) $user->freight_price_per_kg,
                'operation_fee' => (float) $user->operation_fee,
            ],
        ]);
    }
}
