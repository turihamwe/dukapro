<?php

use App\Services\CustomerService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreditCustomerEnhancements extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedSmallInteger('payment_terms_days')->default(30)->after('credit_limit');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('invoice_due_at')->nullable()->after('credit_settled_at');
        });

        $service = app(CustomerService::class);
        $service->normalizeAllPhones();
        $service->dedupeAllBusinesses();

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'phone']);
            $table->unique(['business_id', 'phone'], 'customers_business_phone_unique');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_business_phone_unique');
            $table->index(['business_id', 'phone']);
            $table->dropColumn('payment_terms_days');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('invoice_due_at');
        });
    }
}
