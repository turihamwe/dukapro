<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentWallet;
use App\Models\User;
use App\Support\PaymentWalletMode;
use App\Support\WalletTransactionSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    protected PaymentWalletService $walletService;

    public function __construct(PaymentWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

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
        $amount = round((float) $data['amount'], 2);
        [$walletId, $paymentMethod] = $this->resolvePaymentFields($user, $data);

        return DB::transaction(function () use ($user, $data, $category, $amount, $walletId, $paymentMethod) {
            if ($walletId) {
                $wallet = $this->walletService->resolveForBusiness((int) $user->business_id, $walletId);
                $this->walletService->withdraw($wallet, $amount, $user, [
                    'source_type' => WalletTransactionSource::EXPENSE,
                    'notes' => $data['title'],
                    'reference' => $data['receipt_reference'] ?? null,
                ]);
            }

            return Expense::create([
                'business_id' => $user->business_id,
                'user_id' => $user->id,
                'title' => $data['title'],
                'category' => $category,
                'description' => $data['description'] ?? $data['notes'] ?? '',
                'amount' => $amount,
                'expense_date' => $data['expense_date'],
                'payment_method' => $paymentMethod,
                'payment_wallet_id' => $walletId,
                'receipt_reference' => $data['receipt_reference'] ?? null,
            ]);
        });
    }

    public function update(Expense $expense, User $user, array $data): Expense
    {
        $category = $this->resolveCategorySlug((int) $expense->business_id, $data['category']);
        $amount = round((float) $data['amount'], 2);
        [$walletId, $paymentMethod] = $this->resolvePaymentFields($user, $data);

        return DB::transaction(function () use ($expense, $user, $data, $category, $amount, $walletId, $paymentMethod) {
            $this->refundWalletForExpense($user, $expense);

            if ($walletId) {
                $wallet = $this->walletService->resolveForBusiness((int) $user->business_id, $walletId);
                $this->walletService->withdraw($wallet, $amount, $user, [
                    'source_type' => WalletTransactionSource::EXPENSE,
                    'notes' => $data['title'],
                    'reference' => $data['receipt_reference'] ?? null,
                ]);
            }

            $expense->update([
                'title' => $data['title'],
                'category' => $category,
                'description' => $data['description'] ?? $data['notes'] ?? '',
                'amount' => $amount,
                'expense_date' => $data['expense_date'],
                'payment_method' => $paymentMethod,
                'payment_wallet_id' => $walletId,
                'receipt_reference' => $data['receipt_reference'] ?? null,
            ]);

            return $expense->fresh();
        });
    }

    public function delete(Expense $expense, User $user): void
    {
        DB::transaction(function () use ($expense, $user) {
            $this->refundWalletForExpense($user, $expense);
            $expense->delete();
        });
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function resolvePaymentFields(User $user, array $data): array
    {
        $business = $user->business;

        if (! PaymentWalletMode::active($business)) {
            return [null, $data['payment_method'] ?? 'cash'];
        }

        $walletId = isset($data['payment_wallet_id']) && $data['payment_wallet_id'] !== ''
            ? (int) $data['payment_wallet_id']
            : null;

        $this->walletService->assertWalletRequired((int) $business->id, $walletId);

        if (! $walletId) {
            return [null, $data['payment_method'] ?? 'cash'];
        }

        $wallet = $this->walletService->resolveForBusiness((int) $business->id, $walletId);

        return [$wallet->id, $wallet->type];
    }

    protected function refundWalletForExpense(User $user, Expense $expense): void
    {
        if (! $expense->payment_wallet_id) {
            return;
        }

        $wallet = PaymentWallet::query()
            ->where('business_id', $user->business_id)
            ->whereKey($expense->payment_wallet_id)
            ->first();

        if (! $wallet) {
            return;
        }

        $this->walletService->deposit($wallet, (float) $expense->amount, $user, [
            'source_type' => WalletTransactionSource::ADJUSTMENT_IN,
            'notes' => 'Expense removed: ' . $expense->title,
            'reference' => $expense->receipt_reference,
        ]);
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
