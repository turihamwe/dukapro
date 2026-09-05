<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RestaurantTableService
{
    public function listForUser(User $user): Collection
    {
        return RestaurantTable::query()
            ->where('business_id', $user->business_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function optionsForOrder(User $user): array
    {
        return $this->listForUser($user)
            ->mapWithKeys(fn (RestaurantTable $table) => [$table->id => $table->displayLabel()])
            ->all();
    }

    public function create(Business $business, array $data): RestaurantTable
    {
        $branchId = (int) $data['branch_id'];

        $this->assertBranch($business, $branchId);

        return RestaurantTable::create([
            'business_id' => $business->id,
            'branch_id' => $branchId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        if (isset($data['branch_id'])) {
            $this->assertBranch($table->business, (int) $data['branch_id']);
            $table->branch_id = (int) $data['branch_id'];
        }

        $table->fill([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'is_active' => $data['is_active'] ?? $table->is_active,
            'sort_order' => (int) ($data['sort_order'] ?? $table->sort_order),
        ])->save();

        return $table->fresh();
    }

    public function resolveForOrder(User $user, Business $business, array $payload): array
    {
        if (! $business->usesRestaurantTables()) {
            return [
                'restaurant_table_id' => null,
                'table_label' => $payload['table_label'] ?? null,
            ];
        }

        $tableId = (int) ($payload['restaurant_table_id'] ?? 0);

        if ($tableId <= 0) {
            throw ValidationException::withMessages([
                'restaurant_table_id' => 'Select a table for this order.',
            ]);
        }

        $table = RestaurantTable::query()
            ->where('business_id', $business->id)
            ->where('id', $tableId)
            ->where('is_active', true)
            ->first();

        if (! $table) {
            throw ValidationException::withMessages([
                'restaurant_table_id' => 'The selected table is invalid.',
            ]);
        }

        if ($user->isBranchScoped() && $user->branch_id && (int) $table->branch_id !== (int) $user->branch_id) {
            throw ValidationException::withMessages([
                'restaurant_table_id' => 'That table belongs to another branch.',
            ]);
        }

        return [
            'restaurant_table_id' => $table->id,
            'table_label' => $table->displayLabel(),
        ];
    }

    protected function assertBranch(Business $business, int $branchId): void
    {
        $exists = Branch::query()
            ->where('business_id', $business->id)
            ->where('id', $branchId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected branch is invalid.',
            ]);
        }
    }
}
