<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (SystemSetting::query()->where('key', 'supplier_credit_platform_enabled')->doesntExist()) {
            SystemSetting::set('supplier_credit_platform_enabled', '0');
        }
    }

    public function down(): void
    {
        SystemSetting::query()->where('key', 'supplier_credit_platform_enabled')->delete();
    }
};
