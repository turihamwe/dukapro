<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEfrisFieldsToSalesTable extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('efris_fdn', 64)->nullable()->after('notes');
            $table->string('efris_antifake_code', 64)->nullable()->after('efris_fdn');
            $table->text('efris_qr_code')->nullable()->after('efris_antifake_code');
            $table->string('efris_status', 20)->nullable()->after('efris_qr_code');
            $table->text('efris_error')->nullable()->after('efris_status');
            $table->timestamp('efris_submitted_at')->nullable()->after('efris_error');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'efris_fdn',
                'efris_antifake_code',
                'efris_qr_code',
                'efris_status',
                'efris_error',
                'efris_submitted_at',
            ]);
        });
    }
}
