<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NullUserForeignKeysOnAccountDelete extends Migration
{
    /**
     * Actor columns that used to delete shop records when a login was removed.
     * Account deletion should drop the person, not the books.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    protected array $columns = [
        ['branch_stock_transfers', 'user_id'],
        ['damages', 'user_id'],
        ['end_of_day_reconciliations', 'user_id'],
        ['shift_waiter_balances', 'cashier_user_id'],
        ['shift_waiter_balances', 'waiter_user_id'],
        ['kitchen_orders', 'waiter_id'],
        ['kitchen_orders', 'placed_by_user_id'],
        ['shift_waiter_rosters', 'waiter_user_id'],
        ['shift_waiter_rosters', 'selected_by_user_id'],
        ['reconciliation_shortages', 'user_id'],
        ['reconciliation_shortages', 'recorded_by_user_id'],
    ];

    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->columns as [$table, $column]) {
            if (! $this->columnExists($table, $column)) {
                continue;
            }

            $this->retarget($table, $column);
        }
    }

    public function down()
    {
        // Leaving the columns nullable is safe. Restoring cascade deletes would
        // remove shop records the next time a login is deleted.
    }

    protected function columnExists(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->exists();
    }

    protected function retarget(string $table, string $column): void
    {
        $database = DB::getDatabaseName();
        $name = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', 'users')
            ->value('CONSTRAINT_NAME');

        if ($name) {
            DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$name.'`');
        }

        DB::statement('ALTER TABLE `'.$table.'` MODIFY `'.$column.'` BIGINT UNSIGNED NULL');
        DB::statement(
            'ALTER TABLE `'.$table.'` ADD CONSTRAINT `'.$table.'_'.$column.'_foreign` FOREIGN KEY (`'.$column.'`) REFERENCES `users` (`id`) ON DELETE SET NULL'
        );
    }
}
