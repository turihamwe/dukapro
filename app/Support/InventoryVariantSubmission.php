<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InventoryVariantSubmission
{
    /**
     * @param  array<int, mixed>  $rawVariants
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeAndValidate(array $rawVariants): array
    {
        $kept = [];
        $errors = [];

        foreach ($rawVariants as $index => $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $hasId = ! empty($variant['id']);
            $priceFilled = self::fieldFilled($variant['price'] ?? null);
            $stockFilled = self::fieldFilled($variant['stock_quantity'] ?? null);
            $label = self::describeVariant($variant);

            if ($hasId) {
                if (! $priceFilled) {
                    $errors["variants.{$index}.price"] = "Price is required for {$label}.";
                }
                if (! $stockFilled) {
                    $errors["variants.{$index}.stock_quantity"] = "Stock is required for {$label}.";
                }
                if ($priceFilled && $stockFilled) {
                    $kept[] = self::normalizeCompleteRow($variant);
                }

                continue;
            }

            if (! $priceFilled && ! $stockFilled) {
                continue;
            }

            if ($priceFilled && $stockFilled) {
                $kept[] = self::normalizeCompleteRow($variant);

                continue;
            }

            if (! $priceFilled) {
                $errors["variants.{$index}.price"] = "Enter a price for {$label}, or leave both price and stock blank to skip this variant.";
            }
            if (! $stockFilled) {
                $errors["variants.{$index}.stock_quantity"] = "Enter stock for {$label}, or leave both price and stock blank to skip this variant.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($kept === []) {
            throw ValidationException::withMessages([
                'variants' => 'Add at least one variant with both price and stock filled in.',
            ]);
        }

        $validator = Validator::make(
            ['variants' => $kept],
            [
                'variants' => 'array|min:1',
                'variants.*.id' => 'nullable|integer',
                'variants.*.attribute_values' => 'required|array|min:1',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.stock_quantity' => 'required|numeric|min:0',
                'variants.*.cost_price' => 'nullable|numeric|min:0',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated()['variants'];
    }

    protected static function fieldFilled($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $variant
     */
    protected static function describeVariant(array $variant): string
    {
        $attrs = $variant['attribute_values'] ?? [];
        if (! is_array($attrs) || $attrs === []) {
            return 'this variant row';
        }

        ksort($attrs);
        $parts = [];
        foreach ($attrs as $name => $value) {
            $parts[] = $name . ': ' . $value;
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<string, mixed>  $variant
     * @return array<string, mixed>
     */
    protected static function normalizeCompleteRow(array $variant): array
    {
        $row = $variant;
        $row['price'] = (float) $variant['price'];
        $row['stock_quantity'] = (float) $variant['stock_quantity'];

        if (array_key_exists('cost_price', $variant) && self::fieldFilled($variant['cost_price'])) {
            $row['cost_price'] = (float) $variant['cost_price'];
        } else {
            unset($row['cost_price']);
        }

        if (! empty($variant['id'])) {
            $row['id'] = (int) $variant['id'];
        }

        return $row;
    }
}
