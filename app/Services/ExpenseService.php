<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Carbon\Carbon;

class ExpenseService
{
    public const DEFAULT_CATEGORIES = [
        'rent' => 'Rent',
        'utilities' => 'Utilities',
        'supplies' => 'Supplies',
        'transport' => 'Transport',
        'salaries' => 'Salaries',
        'maintenance' => 'Maintenance',
        'marketing' => 'Marketing',
        'other' => 'Other',
    ];

    /** @deprecated Use categoriesForBusiness() */
    public const CATEGORIES = self::DEFAULT_CATEGORIES;

    public function categoriesForBusiness(int $businessId): array
    {
        $categories = ExpenseCategory::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();

        if (! empty($categories)) {
            return $categories;
        }

        return self::DEFAULT_CATEGORIES;
    }

    public function categoryLabel(int $businessId, string $slug): string
    {
        $categories = $this->categoriesForBusiness($businessId);

        return $categories[$slug] ?? ucfirst(str_replace(['-', '_'], ' ', $slug));
    }

    public function seedDefaultCategories(int $businessId): void
    {
        foreach (self::DEFAULT_CATEGORIES as $slug => $name) {
            ExpenseCategory::firstOrCreate(
                ['business_id' => $businessId, 'slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
        }
    }

    public function resolveCategorySlug(int $businessId, string $category): string
    {
        $category = trim($category);
        $categories = $this->categoriesForBusiness($businessId);

        if (isset($categories[$category])) {
            return $category;
        }

        foreach ($categories as $slug => $name) {
            if (strcasecmp($name, $category) === 0) {
                return $slug;
            }
        }

        $created = ExpenseCategory::create([
            'business_id' => $businessId,
            'name' => $category,
            'slug' => ExpenseCategory::uniqueSlug($businessId, $category),
            'is_active' => true,
        ]);

        return $created->slug;
    }

    public function create(User $user, array $data): Expense
    {
        $category = $this->resolveCategorySlug((int) $user->business_id, $data['category']);

        return Expense::create([
            'business_id' => $user->business_id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'category' => $category,
            'description' => $data['description'] ?? $data['notes'] ?? '',
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'receipt_reference' => $data['receipt_reference'] ?? null,
        ]);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $category = $this->resolveCategorySlug((int) $expense->business_id, $data['category']);

        $expense->update([
            'title' => $data['title'],
            'category' => $category,
            'description' => $data['description'] ?? $data['notes'] ?? '',
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? $expense->payment_method,
            'receipt_reference' => $data['receipt_reference'] ?? null,
        ]);

        return $expense->fresh();
    }

    public function totalForDate(Business $business, Carbon $date): float
    {
        return (float) Expense::query()
            ->where('business_id', $business->id)
            ->whereDate('expense_date', $date)
            ->sum('amount');
    }

    public function totalForRange(Business $business, Carbon $start, Carbon $end): float
    {
        return (float) Expense::query()
            ->where('business_id', $business->id)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }
}
