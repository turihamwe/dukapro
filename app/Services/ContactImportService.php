<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContactImportService
{
    public const FIELDS = [
        'name' => 'Full name',
        'company_name' => 'Company',
        'phone' => 'Phone',
        'email' => 'Email',
        'address' => 'Address',
        'notes' => 'Notes',
        'is_credit_customer' => 'Credit customer (yes/no)',
        'credit_limit' => 'Credit limit',
    ];

    public function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to read the CSV file.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            throw new \RuntimeException('The CSV file is empty.');
        }

        $headers = array_map(function ($header) {
            return Str::slug(trim((string) $header), '_');
        }, $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = trim((string) ($data[$index] ?? ''));
            }
            $rows[] = $row;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function suggestMapping(array $csvHeaders): array
    {
        $aliases = [
            'name' => ['name', 'full_name', 'contact_name', 'customer_name'],
            'company_name' => ['company', 'company_name', 'organization', 'org'],
            'phone' => ['phone', 'mobile', 'telephone', 'phone_number'],
            'email' => ['email', 'email_address'],
            'address' => ['address', 'location'],
            'notes' => ['notes', 'note', 'comments'],
            'is_credit_customer' => ['credit', 'is_credit', 'credit_customer', 'is_credit_customer'],
            'credit_limit' => ['credit_limit', 'limit'],
        ];

        $mapping = [];
        foreach (self::FIELDS as $field => $label) {
            $mapping[$field] = null;
            foreach ($csvHeaders as $header) {
                if (in_array($header, $aliases[$field] ?? [$field], true)) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    public function importRows(Business $business, array $rows, array $mapping): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $payload = $this->mapRow($row, $mapping);

            $validator = Validator::make($payload, [
                'name' => 'required|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:30',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'notes' => 'nullable|string',
                'is_credit_customer' => 'boolean',
                'credit_limit' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = "Row {$line}: " . implode(' ', $validator->errors()->all());

                continue;
            }

            $data = $validator->validated();
            $isCredit = (bool) ($data['is_credit_customer'] ?? false);

            Customer::create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'company_name' => $data['company_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_credit_customer' => $isCredit,
                'credit_limit' => $isCredit ? (float) ($data['credit_limit'] ?? 0) : 0,
                'is_active' => true,
            ]);

            $imported++;
        }

        return compact('imported', 'skipped', 'errors');
    }

    protected function mapRow(array $row, array $mapping): array
    {
        $payload = [];
        foreach (self::FIELDS as $field => $label) {
            $header = $mapping[$field] ?? null;
            $value = $header && isset($row[$header]) ? $row[$header] : '';

            if ($field === 'is_credit_customer') {
                $payload[$field] = in_array(strtolower($value), ['1', 'yes', 'true', 'y'], true);
            } elseif ($field === 'credit_limit') {
                $payload[$field] = $value === '' ? 0 : (float) str_replace(',', '', $value);
            } else {
                $payload[$field] = $value !== '' ? $value : null;
            }
        }

        if (empty($payload['name'])) {
            $payload['name'] = $payload['company_name'] ?? $payload['email'] ?? $payload['phone'] ?? null;
        }

        return $payload;
    }
}
