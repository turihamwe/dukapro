<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliateFieldFeedbackTable extends Migration
{
    public function up()
    {
        Schema::create('affiliate_field_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 50);
            $table->string('merchant_name', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('summary', 200);
            $table->text('message');
            $table->string('contact_phone', 30)->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('affiliate_field_feedback');
    }
}
