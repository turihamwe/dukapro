<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErrorLogsTable extends Migration
{
    public function up()
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('environment', 20);
            $table->string('exception_class')->nullable();
            $table->text('error_message');
            $table->longText('stack_trace')->nullable();
            $table->string('url', 2048)->nullable();
            $table->json('device_info')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'environment']);
            $table->index(['business_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('error_logs');
    }
}
