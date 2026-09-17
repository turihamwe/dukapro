<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEfrisSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('efris_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('efris_enabled')->default(false);
            $table->string('efris_tin', 20)->nullable();
            $table->text('efris_api_token')->nullable();
            $table->string('efris_environment', 20)->default('sandbox');
            $table->string('efris_branch_id', 50)->nullable();
            $table->string('default_buyer_tin', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('efris_settings');
    }
}
