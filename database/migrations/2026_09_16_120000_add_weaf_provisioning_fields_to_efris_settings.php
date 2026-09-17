<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeafProvisioningFieldsToEfrisSettings extends Migration
{
    public function up()
    {
        Schema::table('efris_settings', function (Blueprint $table) {
            $table->string('weaf_email')->nullable()->after('efris_api_token');
            $table->text('weaf_password')->nullable()->after('weaf_email');
            $table->timestamp('weaf_token_expires_at')->nullable()->after('weaf_password');
            $table->string('provisioning_status', 40)->default('not_started')->after('weaf_token_expires_at');
            $table->text('provisioning_error')->nullable()->after('provisioning_status');
        });
    }

    public function down()
    {
        Schema::table('efris_settings', function (Blueprint $table) {
            $table->dropColumn([
                'weaf_email',
                'weaf_password',
                'weaf_token_expires_at',
                'provisioning_status',
                'provisioning_error',
            ]);
        });
    }
}
