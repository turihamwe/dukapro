<?php

use App\Services\CustomerService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreditCustomerEnhancements extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('customers', 'payment_terms_days')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedSmallInteger('payment_terms_days')->default(30)->after('credit_limit');
            });
        }

        if (! Schema::hasColumn('sales', 'invoice_due_at')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->timestamp('invoice_due_at')->nullable()->after('credit_settled_at');
            });
        }

        $service = app(CustomerService::class);
        $service->normalizeAllPhones();
        $service->dedupeAllBusinesses();

        // Add the unique index before dropping the old composite index — MySQL keeps
        // the business_id foreign key on that index until a replacement exists.
        if (! $this->indexExists('customers', 'customers_business_phone_unique')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unique(['business_id', 'phone'], 'customers_business_phone_unique');
            });
        }

        if ($this->indexExists('customers', 'customers_business_id_phone_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['business_id', 'phone']);
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('customers', 'customers_business_phone_unique')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('customers_business_phone_unique');
            });
        }

        if (! $this->indexExists('customers', 'customers_business_id_phone_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['business_id', 'phone']);
            });
        }

        if (Schema::hasColumn('customers', 'payment_terms_days')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('payment_terms_days');
            });
        }

        if (Schema::hasColumn('sales', 'invoice_due_at')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('invoice_due_at');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $table) . '` WHERE Key_name = ?', [$indexName]);

        return count($indexes) > 0;
    }
}
