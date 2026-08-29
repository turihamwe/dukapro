<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;

class ExpenseService
{
    public const CATEGORIES = [
        'rent' => 'Rent',
        'utilities' => 'Utilities',
        'supplies' => 'Supplies',
        'transport' => 'Transport',
        'salaries' => 'Salaries',
        'maintenance' => 'Maintenance',
        'marketing' => 'Marketing',
        'other' => 'Other',
    ];

    public function create(User $user, array $data): Expense
    {
        return Expense::create([
            'business_id' => $user->business_id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'] ?? $data['notes'] ?? null,
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'receipt_reference' => $data['receipt_reference'] ?? null,
        ]);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update([
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'] ?? $data['notes'] ?? null,
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
