<div class="space-y-5">
    <x-input type="text" name="name" label="Brand name" value="{{ old('name', $brand->name ?? '') }}" required autofocus placeholder="e.g. Guinness, Hima Cement" />
    <x-textarea name="description" label="Description (optional)" rows="2">{{ old('description', $brand->description ?? '') }}</x-textarea>
    @if(isset($brand))
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', $brand->is_active) ? 'checked' : '' }}>
            Active (available when adding products)
        </label>
    @endif
</div>
