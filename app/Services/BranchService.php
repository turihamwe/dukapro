<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function createDefault(Business $business, string $name = 'Main Branch'): Branch
    {
        return $this->create($business, [
            'name' => $name,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function create(Business $business, array $data): Branch
    {
        $name = trim($data['name']);

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Branch name is required.']);
        }

        $slug = $this->uniqueSlug($business, Str::slug($name) ?: 'branch');
        $isDefault = (bool) ($data['is_default'] ?? false);

        if ($isDefault) {
            Branch::query()
                ->where('business_id', $business->id)
                ->update(['is_default' => false]);
        }

        return Branch::create([
            'business_id' => $business->id,
            'name' => $name,
            'slug' => $slug,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_default' => $isDefault,
        ]);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $name = trim($data['name'] ?? $branch->name);

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Branch name is required.']);
        }

        if ($name !== $branch->name) {
            $branch->slug = $this->uniqueSlug($branch->business, Str::slug($name) ?: 'branch', $branch->id);
            $branch->name = $name;
        }

        $branch->address = $data['address'] ?? null;
        $branch->phone = $data['phone'] ?? null;
        $branch->is_active = (bool) ($data['is_active'] ?? $branch->is_active);

        if (! empty($data['is_default'])) {
            Branch::query()
                ->where('business_id', $branch->business_id)
                ->where('id', '!=', $branch->id)
                ->update(['is_default' => false]);
            $branch->is_default = true;
        }

        $branch->save();

        return $branch->fresh();
    }

    public function canDelete(Branch $branch): bool
    {
        if ($branch->is_default) {
            return false;
        }

        return ! $branch->users()->exists();
    }

    protected function uniqueSlug(Business $business, string $base, ?int $ignoreId = null): string
    {
        $slug = $base ?: 'branch';
        $counter = 1;

        while (
            Branch::query()
                ->where('business_id', $business->id)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
