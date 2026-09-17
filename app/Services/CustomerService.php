<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone));

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 9) {
            return '256' . $digits;
        }

        if (strlen($digits) === 10 && $digits[0] === '0') {
            return '256' . substr($digits, 1);
        }

        return $digits;
    }

    public function findByPhone(int $businessId, ?string $phone): ?Customer
    {
        $normalized = $this->normalizePhone($phone);

        if ($normalized === null) {
            return null;
        }

        return Customer::query()
            ->where('business_id', $businessId)
            ->where('phone', $normalized)
            ->first();
    }

    public function findOrCreate(int $businessId, array $data, User $user, bool $forceCredit = false): array
    {
        $normalizedPhone = $this->normalizePhone($data['phone'] ?? null);

        if ($normalizedPhone === null) {
            throw ValidationException::withMessages([
                'phone' => 'Phone number is required for credit customers.',
            ]);
        }

        $existing = $this->findByPhone($businessId, $normalizedPhone);

        if ($existing) {
            if ($forceCredit && ! $existing->is_credit_customer) {
                $existing->update([
                    'is_credit_customer' => true,
                    'credit_limit' => max((float) $existing->credit_limit, (float) ($data['credit_limit'] ?? 0)),
                    'payment_terms_days' => (int) ($data['payment_terms_days'] ?? $existing->payment_terms_days ?? 30),
                ]);
            }

            return [
                'customer' => $existing->fresh(),
                'created' => false,
            ];
        }

        $isCredit = $forceCredit || (bool) ($data['is_credit_customer'] ?? false);

        $customer = Customer::create([
            'business_id' => $businessId,
            'name' => trim((string) $data['name']),
            'company_name' => $data['company_name'] ?? null,
            'phone' => $normalizedPhone,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'credit_limit' => $isCredit ? (float) ($data['credit_limit'] ?? 0) : 0,
            'payment_terms_days' => isset($data['payment_terms_days']) ? (int) $data['payment_terms_days'] : 30,
            'is_credit_customer' => $isCredit,
            'is_active' => true,
        ]);

        return [
            'customer' => $customer,
            'created' => true,
        ];
    }

    public function preparePhoneForStorage(?string $phone): ?string
    {
        return $this->normalizePhone($phone);
    }

    public function dedupeBusinessPhones(int $businessId): void
    {
        $customers = Customer::query()
            ->where('business_id', $businessId)
            ->whereNotNull('phone')
            ->orderBy('id')
            ->get();

        $seen = [];

        foreach ($customers as $customer) {
            $normalized = $this->normalizePhone($customer->phone);

            if ($normalized === null) {
                continue;
            }

            if (isset($seen[$normalized])) {
                $customer->update(['phone' => null]);

                continue;
            }

            $seen[$normalized] = true;

            if ($customer->phone !== $normalized) {
                $customer->update(['phone' => $normalized]);
            }
        }
    }

    public function normalizeAllPhones(): void
    {
        Customer::query()
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(200, function ($customers) {
                foreach ($customers as $customer) {
                    $normalized = $this->normalizePhone($customer->phone);

                    if ($normalized !== null && $customer->phone !== $normalized) {
                        $customer->update(['phone' => $normalized]);
                    }
                }
            });
    }

    public function dedupeAllBusinesses(): void
    {
        $businessIds = Customer::query()
            ->distinct()
            ->pluck('business_id');

        foreach ($businessIds as $businessId) {
            $this->dedupeBusinessPhones((int) $businessId);
        }
    }
}
