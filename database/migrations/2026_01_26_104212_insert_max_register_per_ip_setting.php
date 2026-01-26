<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SiteSetting::set('max_register_per_ip', 3, 'integer');

        $setting = SiteSetting::find('max_register_per_ip');
        if ($setting) {
            $setting->update([
                'label' => '单IP每日最大注册数',
                'description' => '限制同一个IP地址每24小时内允许注册的账号数量。0表示不限制。',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SiteSetting::where('key', 'max_register_per_ip')->delete();
    }
};
