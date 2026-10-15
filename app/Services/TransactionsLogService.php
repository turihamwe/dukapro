<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CustomerCreditMode;
use App\Support\PaymentWalletMode;
use App\Support\SupplierCreditMode;
use App\Support\WalletTransactionSource;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionsLogService
{
    public function forRequest(Request $request): array
    {
        $user = $request->user();
        $business = $user->business;
        $types = $this->availableTypes($user);
        $type = (string) $request->input('type', 'all');

        if (! array_key_exists($type, $types)) {
            $type = 'all';
        }

        $period = $this->resolvePeriod($request);
        $branch = $this->resolveBranch($business, $user, $request);
        $entity = $this->resolveEntity($business, $type, $request);
        $rows = $this->paginate($business, $type, $period['start'], $period['end'], $branch['branch_id'], $entity, (int) $request->input('page', 1));

        return [
            'rows' => $rows,
            'type' => $type,
            'types' => $types,
            'range' => $period['key'],
            'rangeLabel' => $period['label'],
            'startDate' => $period['start']->toDateString(),
            'endDate' => $period['end']->toDateString(),
            'branch' => $branch,
            'entity' => $entity,
            'notice' => $this->noticeFor($business, (string) $request->input('type', 'all')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function availableTypes(User $user): array
    {
        $types = [
            'all' => 'All',
        ];

        $business = $user->business;

        if (SupplierCreditMode::active($business)) {
            $types['supplier'] = 'Suppliers';
        }

        if (CustomerCreditMode::active($business)) {
            $types['customer'] = 'Customers';
        }

        $types['product'] = 'Products';

        if (PaymentWalletMode::active($business)) {
            $types['wallet'] = 'Wallets';
        }

        return $types;
    }

    protected function paginate(Business $business, string $type, Carbon $start, Carbon $end, ?int $branchId, array $entity, int $page): LengthAwarePaginator
    {
        $parts = $this->sources($business, $type, $start, $end, $branchId, $entity);
        $page = max(1, $page);
        $perPage = 40;

        if ($parts === []) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        $union = array_shift($parts);
        foreach ($parts as $part) {
            $union->unionAll($part);
        }

        $total = (int) DB::query()->fromSub(clone $union, 'transaction_log')->count();
        $items = DB::query()
            ->fromSub(clone $union, 'transaction_log')
            ->orderByDesc('occurred_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) {
                $row->occurred_label = $row->occurred_at
                    ? Carbon::parse($row->occurred_at)->format('M j, Y g:i A')
                    : '—';
                $row->impact_label = $this->formatImpact($row);
                $row->reference_no = $row->reference_no !== null && $row->reference_no !== '' ? $row->reference_no : '—';
                $row->entity_name = $row->entity_name !== null && $row->entity_name !== '' ? $row->entity_name : '—';
                $row->branch_name = $row->branch_name !== null && $row->branch_name !== '' ? $row->branch_name : '—';

                return $row;
            });

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * @param  array{supplier_id: ?int, customer_id: ?int, product_id: ?int, product_ids: array<int, int>, name: ?string}  $entity
     * @return array<int, Builder>
     */
    protected function sources(Business $business, string $type, Carbon $start, Carbon $end, ?int $branchId, array $entity): array
    {
        $parts = [];
        $include = function (string $source) use ($type): bool {
            return $type === 'all' || $type === $source;
        };

        if ($include('supplier') && SupplierCreditMode::active($business)) {
            $parts[] = $this->supplierBills($business->id, $start, $end, $branchId, $entity['supplier_id']);
            $parts[] = $this->supplierPayments($business->id, $start, $end, $branchId, $entity['supplier_id']);
        }

        if ($include('customer') && CustomerCreditMode::active($business)) {
            $parts[] = $this->customerSales($business->id, $start, $end, $branchId, $entity['customer_id']);
            $parts[] = $this->customerLedger($business->id, $start, $end, $branchId, $entity['customer_id']);
        }

        if ($include('product')) {
            $parts[] = $this->productSales($business->id, $start, $end, $branchId, $entity['product_ids']);
            $parts[] = $this->productReceipts($business->id, $start, $end, $branchId, $entity['product_ids']);
            $parts[] = $this->productDamages($business->id, $start, $end, $branchId, $entity['product_ids']);
            $parts[] = $this->productTransfers($business->id, $start, $end, $branchId, $entity['product_ids']);
        }

        if ($include('wallet') && PaymentWalletMode::active($business)) {
            $parts[] = $this->walletTransactions($business->id, $start, $end);
        }

        return $parts;
    }

    protected function supplierBills(int $businessId, Carbon $start, Carbon $end, ?int $branchId, ?int $supplierId = null): Builder
    {
        $activity = "CASE
            WHEN p.is_opening_balance = 1 THEN 'Opening balance'
            WHEN p.is_historical_adjustment = 1 THEN 'Adjustment'
            ELSE 'Bill'
        END";

        return $this->project(DB::table('supplier_credit_purchases as p')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->leftJoin('branches as b', 'b.id', '=', 'p.branch_id')
            ->where('p.business_id', $businessId)
            ->whereNull('p.deleted_at')
            ->whereBetween('p.purchase_date', [$start->toDateString(), $end->toDateString()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where('p.branch_id', $branchId);
            })
            ->when($supplierId, function (Builder $query) use ($supplierId) {
                $query->where('p.supplier_id', $supplierId);
            }), [
            'occurred_at' => 'TIMESTAMP(p.purchase_date, TIME(p.created_at))',
            'category' => "'Supplier'",
            'entity_name' => "COALESCE(s.name, 'Supplier')",
            'activity' => $this->withNote($activity, 'p.notes'),
            'reference_no' => "COALESCE(NULLIF(p.reference, ''), '—')",
            'impact_value' => 'p.total_amount',
            'impact_kind' => "'money'",
            'impact_unit' => "''",
            'branch_name' => "COALESCE(b.name, '—')",
        ]);
    }

    protected function supplierPayments(int $businessId, Carbon $start, Carbon $end, ?int $branchId, ?int $supplierId = null): Builder
    {
        return $this->project(DB::table('supplier_credit_payments as pay')
            ->leftJoin('suppliers as s', 's.id', '=', 'pay.supplier_id')
            ->leftJoin('supplier_credit_purchases as bill', 'bill.id', '=', 'pay.supplier_credit_purchase_id')
            ->leftJoin('branches as b', 'b.id', '=', 'bill.branch_id')
            ->where('pay.business_id', $businessId)
            ->whereNull('pay.deleted_at')
            ->whereBetween('pay.paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where('bill.branch_id', $branchId);
            })
            ->when($supplierId, function (Builder $query) use ($supplierId) {
                $query->where('pay.supplier_id', $supplierId);
            }), [
            'occurred_at' => 'pay.paid_at',
            'category' => "'Supplier'",
            'entity_name' => "COALESCE(s.name, 'Supplier')",
            'activity' => $this->withNote("'Payment made'", 'pay.notes'),
            'reference_no' => "COALESCE(NULLIF(pay.reference, ''), NULLIF(bill.reference, ''), '—')",
            'impact_value' => '-pay.amount',
            'impact_kind' => "'money'",
            'impact_unit' => "''",
            'branch_name' => "COALESCE(b.name, '—')",
        ]);
    }

    protected function customerSales(int $businessId, Carbon $start, Carbon $end, ?int $branchId, ?int $customerId = null): Builder
    {
        $activity = "CASE
            WHEN sales.is_credit_sale = 1 THEN 'Invoice'
            WHEN sales.payment_method = 'cash' THEN 'Cash sale'
            WHEN sales.payment_method = 'mobile_money' THEN 'Mobile money sale'
            WHEN sales.payment_method = 'bank' THEN 'Bank sale'
            ELSE 'Sale'
        END";

        return $this->project(DB::table('sales')
            ->leftJoin('customers as c', 'c.id', '=', 'sales.customer_id')
            ->leftJoin('branches as b', 'b.id', '=', 'sales.branch_id')
            ->where('sales.business_id', $businessId)
            ->whereNull('sales.deleted_at')
            ->where('sales.status', 'completed')
            ->where(function (Builder $query) {
                $query->whereNotNull('sales.customer_id')->orWhere('sales.is_credit_sale', 1);
            })
            ->whereBetween('sales.completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where('sales.branch_id', $branchId);
            })
            ->when($customerId, function (Builder $query) use ($customerId) {
                $query->where('sales.customer_id', $customerId);
            }), [
            'occurred_at' => 'sales.completed_at',
            'category' => "'Customer'",
            'entity_name' => "COALESCE(c.name, 'Walk-in')",
            'activity' => $activity,
            'reference_no' => "COALESCE(NULLIF(sales.sale_number, ''), '—')",
            'impact_value' => 'sales.total',
            'impact_kind' => "'money'",
            'impact_unit' => "''",
            'branch_name' => "COALESCE(b.name, '—')",
        ]);
    }

    protected function customerLedger(int $businessId, Carbon $start, Carbon $end, ?int $branchId, ?int $customerId = null): Builder
    {
        $activity = "CASE
            WHEN e.type = 'payment' THEN 'Payment received'
            WHEN e.is_opening_balance = 1 THEN 'Opening balance'
            WHEN e.type = 'credit' THEN 'Credit adjustment'
            ELSE 'Balance entry'
        END";

        return $this->project(DB::table('debt_ledger_entries as e')
            ->leftJoin('customers as c', 'c.id', '=', 'e.customer_id')
            ->leftJoin('sales', 'sales.id', '=', 'e.sale_id')
            ->leftJoin('branches as b', 'b.id', '=', 'sales.branch_id')
            ->where('e.business_id', $businessId)
            ->whereNull('e.deleted_at')
            ->where(function (Builder $query) {
                $query->where('e.type', 'payment')
                    ->orWhere('e.type', 'credit')
                    ->orWhere(function (Builder $query) {
                        $query->where('e.type', 'debit')->whereNull('e.sale_id');
                    });
            })
            ->whereBetween('e.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($customerId, function (Builder $query) use ($customerId) {
                $query->where('e.customer_id', $customerId);
            })
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where(function (Builder $query) use ($branchId) {
                    $query->where('sales.branch_id', $branchId)->orWhereNull('e.sale_id');
                });
            }), [
            'occurred_at' => 'e.created_at',
            'category' => "'Customer'",
            'entity_name' => "COALESCE(c.name, 'Customer')",
            'activity' => $this->withNote($activity, 'e.description'),
            'reference_no' => "COALESCE(NULLIF(sales.sale_number, ''), '—')",
            'impact_value' => "CASE WHEN e.type = 'credit' THEN -e.amount ELSE e.amount END",
            'impact_kind' => "'money'",
            'impact_unit' => "''",
            'branch_name' => "COALESCE(b.name, '—')",
        ]);
    }

    protected function productSales(int $businessId, Carbon $start, Carbon $end, ?int $branchId, array $productIds = []): Builder
    {
        return $this->project(DB::table('sale_items as si')
            ->join('sales', 'sales.id', '=', 'si.sale_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'si.product_id')
            ->leftJoin('branches as b', 'b.id', '=', 'sales.branch_id')
            ->where('sales.business_id', $businessId)
            ->whereNull('sales.deleted_at')
            ->where('sales.status', 'completed')
            ->where(function (Builder $query) {
                $query->where('pr.is_service', 0)->orWhereNull('pr.is_service');
            })
            ->whereBetween('sales.completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where('sales.branch_id', $branchId);
            })
            ->when($productIds !== [], function (Builder $query) use ($productIds) {
                $query->whereIn('si.product_id', $productIds);
            }), [
            'occurred_at' => 'sales.completed_at',
            'category' => "'Product'",
            'entity_name' => "COALESCE(pr.name, si.product_name, 'Product')",
            'activity' => "'Sold'",
            'reference_no' => "COALESCE(NULLIF(sales.sale_number, ''), '—')",
            'impact_value' => '-si.quantity',
            'impact_kind' => "'qty'",
            'impact_unit' => "COALESCE(NULLIF(si.measurement_unit, ''), NULLIF(pr.measurement_unit, ''), 'unit')",
            'branch_name' => "COALESCE(b.name, '—')",
        ]);
    }

    protected function productReceipts(int $businessId, Carbon $start, Carbon $end, ?int $branchId, array $productIds = []): Builder
    {
        return $this->project(DB::table('supplier_credit_purchase_lines as line')
            ->join('supplier_credit_purchases as p', 'p.id', '=', 'line.supplier_credit_purchase_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'line.product_id')
            ->leftJoin('branches as b', 'b.id', '=', 'p.branch_id')
            ->where('p.business_id', $businessId)
            ->whereNull('p.deleted_at')
            ->where('p.is_opening_balance', 0)
            ->where('p.is_historical_adjustment', 0)
            ->where(function (Builder $query) {
                $query->where('pr.is_service', 0)->orWhereNull('pr.is_service');
            })
            ->whereBetween('p.purchase_date', [$start->toDateString(), $end->toDateString()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where('p.branch_id', $branchId);
            })
            ->when($productIds !== [], function (Builder $query) use ($productIds) {
                $query->whereIn('line.product_id', $productIds);
            }), [
            'occurred_at' => 'TIMESTAMP(p.purchase_date, TIME(p.created_at))',
            'category' => "'Product'",
            'entity_name' => "COALESCE(pr.name, 'Product')",
            'activity' => "'Purchase received'",
            'reference_no' => "COALESCE(NULLIF(p.reference, ''), '—')",
            'impact_value' => 'line.quantity',
            'impact_kind' => "'qty'",
            'impact_unit' => "COALESCE(NULLIF(pr.measurement_unit, ''), 'unit')",
            'branch_name' => "COALESCE(b.name, '—')",
        ]);
    }

    protected function productDamages(int $businessId, Carbon $start, Carbon $end, ?int $branchId, array $productIds = []): Builder
    {
        return $this->project(DB::table('damages as d')
            ->leftJoin('products as pr', 'pr.id', '=', 'd.product_id')
            ->leftJoin('branches as b', 'b.id', '=', 'd.branch_id')
            ->where('d.business_id', $businessId)
            ->whereNull('d.deleted_at')
            ->whereBetween('d.damage_date', [$start->toDateString(), $end->toDateString()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where('d.branch_id', $branchId);
            })
            ->when($productIds !== [], function (Builder $query) use ($productIds) {
                $query->whereIn('d.product_id', $productIds);
            }), [
            'occurred_at' => 'TIMESTAMP(d.damage_date, TIME(d.created_at))',
            'category' => "'Product'",
            'entity_name' => "COALESCE(pr.name, 'Product')",
            'activity' => $this->withNote("'Damaged'", 'd.reason'),
            'reference_no' => "'—'",
            'impact_value' => '-d.quantity',
            'impact_kind' => "'qty'",
            'impact_unit' => "COALESCE(NULLIF(pr.measurement_unit, ''), 'unit')",
            'branch_name' => "COALESCE(b.name, '—')",
        ]);
    }

    protected function productTransfers(int $businessId, Carbon $start, Carbon $end, ?int $branchId, array $productIds = []): Builder
    {
        $impact = $branchId
            ? 'CASE WHEN t.from_branch_id = '.(int) $branchId.' THEN -t.quantity ELSE t.quantity END'
            : 't.quantity';

        return $this->project(DB::table('branch_stock_transfers as t')
            ->leftJoin('products as pr', 'pr.id', '=', 't.from_product_id')
            ->leftJoin('branches as fb', 'fb.id', '=', 't.from_branch_id')
            ->leftJoin('branches as tb', 'tb.id', '=', 't.to_branch_id')
            ->where('t.business_id', $businessId)
            ->whereBetween('t.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where(function (Builder $query) use ($branchId) {
                    $query->where('t.from_branch_id', $branchId)->orWhere('t.to_branch_id', $branchId);
                });
            })
            ->when($productIds !== [], function (Builder $query) use ($productIds) {
                $query->where(function (Builder $query) use ($productIds) {
                    $query->whereIn('t.from_product_id', $productIds)->orWhereIn('t.to_product_id', $productIds);
                });
            }), [
            'occurred_at' => 't.created_at',
            'category' => "'Product'",
            'entity_name' => "COALESCE(pr.name, 'Product')",
            'activity' => "'Stock transfer'",
            'reference_no' => "'—'",
            'impact_value' => $impact,
            'impact_kind' => "'qty'",
            'impact_unit' => "COALESCE(NULLIF(pr.measurement_unit, ''), 'unit')",
            'branch_name' => "CONCAT(COALESCE(fb.name, '—'), ' → ', COALESCE(tb.name, '—'))",
        ]);
    }

    protected function walletTransactions(int $businessId, Carbon $start, Carbon $end): Builder
    {
        $cases = '';
        foreach (WalletTransactionSource::labels() as $key => $label) {
            $cases .= "WHEN '".str_replace("'", '', $key)."' THEN '".str_replace("'", "''", $label)."' ";
        }
        $activity = "CASE wt.source_type {$cases} ELSE 'Wallet movement' END";

        return $this->project(DB::table('payment_wallet_transactions as wt')
            ->leftJoin('payment_wallets as w', 'w.id', '=', 'wt.payment_wallet_id')
            ->where('wt.business_id', $businessId)
            ->whereNull('wt.deleted_at')
            ->whereBetween(DB::raw('COALESCE(wt.occurred_at, wt.created_at)'), [$start->copy()->startOfDay(), $end->copy()->endOfDay()]), [
            'occurred_at' => 'COALESCE(wt.occurred_at, wt.created_at)',
            'category' => "'Wallet'",
            'entity_name' => "COALESCE(w.name, 'Wallet')",
            'activity' => $this->withNote($activity, 'wt.notes'),
            'reference_no' => "COALESCE(NULLIF(wt.reference, ''), '—')",
            'impact_value' => "CASE WHEN wt.direction = 'out' THEN -wt.amount ELSE wt.amount END",
            'impact_kind' => "'money'",
            'impact_unit' => "''",
            'branch_name' => "'—'",
        ]);
    }

    /**
     * @param  array<string, string>  $columns
     */
    protected function project(Builder $query, array $columns): Builder
    {
        $sql = [];
        foreach ($columns as $alias => $expression) {
            $sql[] = $expression.' as '.$alias;
        }

        return $query->selectRaw(implode(', ', $sql));
    }

    protected function withNote(string $activitySql, string $column): string
    {
        return "CASE WHEN {$column} IS NULL OR {$column} = '' THEN {$activitySql} ELSE CONCAT({$activitySql}, ' — ', LEFT({$column}, 80)) END";
    }

    protected function formatImpact(object $row): string
    {
        $value = (float) $row->impact_value;

        if ($row->impact_kind === 'money') {
            return format_money($value);
        }

        $sign = $value > 0 ? '+' : ($value < 0 ? '−' : '');
        $quantity = rtrim(rtrim(number_format(abs($value), 3, '.', ''), '0'), '.');
        if ($quantity === '') {
            $quantity = '0';
        }
        $unit = trim((string) $row->impact_unit);

        return trim($sign.$quantity.($unit !== '' ? ' '.$unit : ''));
    }

    /**
     * @return array{supplier_id: ?int, customer_id: ?int, product_id: ?int, product_ids: array<int, int>, name: ?string}
     */
    protected function resolveEntity(Business $business, string $type, Request $request): array
    {
        $entity = [
            'supplier_id' => null,
            'customer_id' => null,
            'product_id' => null,
            'product_ids' => [],
            'name' => null,
        ];

        if ($type === 'supplier') {
            $supplier = Supplier::query()
                ->where('business_id', $business->id)
                ->whereKey((int) $request->input('supplier_id'))
                ->first();

            if ($supplier) {
                $entity['supplier_id'] = (int) $supplier->id;
                $entity['name'] = $supplier->name;
            }
        }

        if ($type === 'customer') {
            $customer = Customer::query()
                ->where('business_id', $business->id)
                ->whereKey((int) $request->input('customer_id'))
                ->first();

            if ($customer) {
                $entity['customer_id'] = (int) $customer->id;
                $entity['name'] = $customer->name;
            }
        }

        if ($type === 'product') {
            $product = Product::query()
                ->withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereNull('deleted_at')
                ->whereKey((int) $request->input('product_id'))
                ->first();

            if ($product) {
                $variantIds = Product::query()
                    ->withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->whereNull('deleted_at')
                    ->where('parent_id', $product->id)
                    ->pluck('id')
                    ->all();

                $entity['product_id'] = (int) $product->id;
                $entity['product_ids'] = array_map('intval', array_merge([$product->id], $variantIds));
                $entity['name'] = $product->name;
            }
        }

        return $entity;
    }

    /**
     * @return array{key: string, label: string, start: Carbon, end: Carbon}
     */
    protected function resolvePeriod(Request $request): array
    {
        $key = (string) $request->input('range', 'month');
        if (! in_array($key, ['day', 'week', 'month', 'year', 'custom'], true)) {
            $key = 'month';
        }

        $from = $request->input('start_date');
        $to = $request->input('end_date');

        if ($key === 'custom' && (! $from || ! $to)) {
            $from = Carbon::today()->startOfMonth()->toDateString();
            $to = Carbon::today()->toDateString();
        }

        $resolved = app(FinancialStatisticsService::class)->resolveRange($key, $from, $to);

        return [
            'key' => $resolved['key'],
            'label' => $resolved['label'],
            'start' => $resolved['start'],
            'end' => $resolved['end'],
        ];
    }

    /**
     * @return array{branch_id: ?int, selected: string, show_picker: bool, branches: \Illuminate\Support\Collection}
     */
    protected function resolveBranch(Business $business, User $user, Request $request): array
    {
        $branches = Branch::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name']);

        $lockedId = BranchContext::id();
        if ($lockedId) {
            return [
                'branch_id' => (int) $lockedId,
                'selected' => (string) $lockedId,
                'show_picker' => false,
                'branches' => $branches,
            ];
        }

        if (! $user->isOwner()) {
            $forced = $user->branch_id ? (int) $user->branch_id : null;
            if ($forced === null && $branches->count() > 1) {
                $default = $branches->first();
                $forced = $default ? (int) $default->id : null;
            }

            return [
                'branch_id' => $forced,
                'selected' => $forced ? (string) $forced : 'all',
                'show_picker' => false,
                'branches' => $branches,
            ];
        }

        if ($branches->count() <= 1) {
            return [
                'branch_id' => null,
                'selected' => 'all',
                'show_picker' => false,
                'branches' => $branches,
            ];
        }

        $selected = (string) $request->input('branch_id', 'all');
        if ($selected === '' || $selected === 'all') {
            return [
                'branch_id' => null,
                'selected' => 'all',
                'show_picker' => true,
                'branches' => $branches,
            ];
        }

        $match = $branches->firstWhere('id', (int) $selected);
        if (! $match) {
            return [
                'branch_id' => null,
                'selected' => 'all',
                'show_picker' => true,
                'branches' => $branches,
            ];
        }

        return [
            'branch_id' => (int) $match->id,
            'selected' => (string) $match->id,
            'show_picker' => true,
            'branches' => $branches,
        ];
    }

    protected function noticeFor(Business $business, string $requested): ?string
    {
        if ($requested === 'supplier' && ! SupplierCreditMode::active($business)) {
            return 'Supplier bills appear here when Supplier credit & accounts payable is on.';
        }

        if ($requested === 'customer' && ! CustomerCreditMode::active($business)) {
            return 'Customer sales and payments appear here when Customer credit & accounts receivable is on.';
        }

        if ($requested === 'wallet' && ! PaymentWalletMode::active($business)) {
            return 'Wallet movements appear here when Wallets / payment accounts is on.';
        }

        return null;
    }
}
