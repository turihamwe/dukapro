<?php

namespace App\Services;

use App\Enums\ShareholderStatus;
use App\Models\Shareholder;
use App\Models\ShareholderEarning;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShareholderEarningsService
{
    public function record(Shareholder $shareholder, float $amount, User $recorder, ?string $description = null, ?string $reference = null): ShareholderEarning
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Earnings amount must be greater than zero.',
            ]);
        }

        if ($shareholder->isContractComplete()) {
            throw ValidationException::withMessages([
                'amount' => 'This shareholder has reached the 3× earnings cap. Contract is complete.',
            ]);
        }

        $remaining = $shareholder->remainingEarningsCapacity();
        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds remaining earnings capacity of UGX ' . number_format($remaining, 0) . '.',
            ]);
        }

        return DB::transaction(function () use ($shareholder, $amount, $recorder, $description, $reference) {
            $earning = ShareholderEarning::create([
                'shareholder_id' => $shareholder->id,
                'amount' => $amount,
                'description' => $description,
                'reference' => $reference,
                'recorded_by' => $recorder->id,
                'paid_at' => now(),
            ]);

            $shareholder->increment('total_earnings', $amount);
            $shareholder->refresh();

            if ($shareholder->total_earnings >= $shareholder->earningsCap()) {
                $shareholder->update([
                    'contract_completed' => true,
                    'contract_completed_at' => now(),
                    'status' => ShareholderStatus::COMPLETED,
                    'is_active' => false,
                ]);

                if ($shareholder->user) {
                    $shareholder->user->update(['is_active' => true]);
                }
            }

            return $earning;
        });
    }
}
