<?php

namespace App\Services;

use App\Models\EndOfDayReconciliation;
use App\Models\ReconciliationShortage;
use App\Models\ShiftWaiterBalance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReconciliationShortageService
{
    public function recordFromReconciliation(EndOfDayReconciliation $reconciliation, User $recordedBy): Collection
    {
        return DB::transaction(function () use ($reconciliation, $recordedBy) {
            $saved = collect();
            $date = $reconciliation->reconciliation_date->toDateString();

            ReconciliationShortage::query()
                ->where('end_of_day_reconciliation_id', $reconciliation->id)
                ->where('status', ReconciliationShortage::STATUS_PENDING)
                ->where('source', ReconciliationShortage::SOURCE_CASHIER_EOD)
                ->delete();

            $balances = ShiftWaiterBalance::query()
                ->where('business_id', $reconciliation->business_id)
                ->where('shift_date', $date)
                ->where(function ($query) use ($reconciliation) {
                    $query->where('end_of_day_reconciliation_id', $reconciliation->id)
                        ->orWhereIn('status', [
                            ShiftWaiterBalance::STATUS_BALANCED,
                            ShiftWaiterBalance::STATUS_SUBMITTED,
                        ]);
                })
                ->get();

            foreach ($balances as $balance) {
                if ($balance->shortage <= 0) {
                    ReconciliationShortage::query()
                        ->where('shift_waiter_balance_id', $balance->id)
                        ->where('status', ReconciliationShortage::STATUS_PENDING)
                        ->delete();

                    continue;
                }

                $existing = ReconciliationShortage::query()
                    ->where('shift_waiter_balance_id', $balance->id)
                    ->first();

                if ($existing && $existing->status !== ReconciliationShortage::STATUS_PENDING) {
                    continue;
                }

                $saved->push(
                    ReconciliationShortage::updateOrCreate(
                        ['shift_waiter_balance_id' => $balance->id],
                        [
                            'business_id' => $reconciliation->business_id,
                            'user_id' => $balance->waiter_user_id,
                            'shortage_date' => $date,
                            'amount' => $balance->shortage,
                            'status' => ReconciliationShortage::STATUS_PENDING,
                            'source' => ReconciliationShortage::SOURCE_WAITER_BALANCE,
                            'end_of_day_reconciliation_id' => $reconciliation->id,
                            'notes' => $balance->notes,
                            'recorded_by_user_id' => $recordedBy->id,
                        ]
                    )
                );
            }

            if (($reconciliation->missing_money ?? 0) > 0) {
                $existingCashier = ReconciliationShortage::query()
                    ->where('end_of_day_reconciliation_id', $reconciliation->id)
                    ->where('source', ReconciliationShortage::SOURCE_CASHIER_EOD)
                    ->where('user_id', $reconciliation->user_id)
                    ->first();

                if (! $existingCashier || $existingCashier->status === ReconciliationShortage::STATUS_PENDING) {
                    $saved->push(
                        ReconciliationShortage::updateOrCreate(
                            [
                                'end_of_day_reconciliation_id' => $reconciliation->id,
                                'source' => ReconciliationShortage::SOURCE_CASHIER_EOD,
                                'user_id' => $reconciliation->user_id,
                            ],
                            [
                                'business_id' => $reconciliation->business_id,
                                'shortage_date' => $date,
                                'amount' => $reconciliation->missing_money,
                                'status' => ReconciliationShortage::STATUS_PENDING,
                                'shift_waiter_balance_id' => null,
                                'notes' => $reconciliation->notes,
                                'recorded_by_user_id' => $recordedBy->id,
                            ]
                        )
                    );
                }
            }

            return $saved;
        });
    }

    public function listForBusiness(int $businessId, ?string $status = null): Collection
    {
        $query = ReconciliationShortage::query()
            ->with(['user', 'reconciliation', 'recordedBy', 'settledBy'])
            ->where('business_id', $businessId)
            ->orderByDesc('shortage_date')
            ->orderByDesc('id');

        if ($status === 'pending') {
            $query->where('status', ReconciliationShortage::STATUS_PENDING);
        } elseif ($status === 'settled') {
            $query->whereIn('status', [
                ReconciliationShortage::STATUS_SETTLED,
                ReconciliationShortage::STATUS_WAIVED,
            ]);
        }

        return $query->get();
    }

    public function settle(User $manager, ReconciliationShortage $shortage, array $data): ReconciliationShortage
    {
        if ((int) $shortage->business_id !== (int) $manager->business_id) {
            abort(404);
        }

        if (! $shortage->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This shortage has already been cleared.',
            ]);
        }

        $amountPaid = (float) ($data['amount_settled'] ?? $shortage->outstandingAmount());

        if ($amountPaid <= 0) {
            throw ValidationException::withMessages([
                'amount_settled' => 'Enter the amount received.',
            ]);
        }

        if ($amountPaid > $shortage->outstandingAmount()) {
            throw ValidationException::withMessages([
                'amount_settled' => 'Amount cannot exceed the outstanding balance.',
            ]);
        }

        $newSettled = round($shortage->amount_settled + $amountPaid, 2);
        $fullyCleared = $newSettled >= $shortage->amount;

        $shortage->update([
            'amount_settled' => $newSettled,
            'status' => $fullyCleared ? ReconciliationShortage::STATUS_SETTLED : ReconciliationShortage::STATUS_PENDING,
            'settled_by_user_id' => $manager->id,
            'settled_at' => now(),
            'settlement_notes' => $data['settlement_notes'] ?? null,
        ]);

        return $shortage->fresh(['user', 'reconciliation', 'settledBy']);
    }

    public function waive(User $manager, ReconciliationShortage $shortage, ?string $notes = null): ReconciliationShortage
    {
        if ((int) $shortage->business_id !== (int) $manager->business_id) {
            abort(404);
        }

        if (! $shortage->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This shortage has already been cleared.',
            ]);
        }

        $shortage->update([
            'status' => ReconciliationShortage::STATUS_WAIVED,
            'settled_by_user_id' => $manager->id,
            'settled_at' => now(),
            'settlement_notes' => $notes,
        ]);

        return $shortage->fresh(['user', 'reconciliation', 'settledBy']);
    }
}
