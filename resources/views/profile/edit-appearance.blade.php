<p class="mb-3 text-sm font-medium text-gray-700">Appearance</p>
<div class="grid gap-3 sm:grid-cols-2">
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition {{ old('ui_theme', $user->ui_theme === 'custom' ? 'modern' : $user->ui_theme) === 'plain' ? 'border-indigo-600 bg-indigo-50 ring-1 ring-indigo-600' : 'border-gray-200 hover:border-gray-300' }}">
        <input type="radio" name="ui_theme" value="plain" class="mt-1 text-indigo-600 focus:ring-indigo-500" {{ old('ui_theme', $user->ui_theme === 'custom' ? 'modern' : $user->ui_theme) === 'plain' ? 'checked' : '' }}>
        <span>
            <span class="block text-sm font-semibold text-gray-900">Plain Theme</span>
            <span class="mt-0.5 block text-xs text-gray-500">Clean default layout with light sidebar.</span>
        </span>
    </label>
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition {{ in_array(old('ui_theme', $user->ui_theme === 'custom' ? 'modern' : $user->ui_theme), ['modern', 'custom']) ? 'border-emerald-600 bg-emerald-50 ring-1 ring-emerald-600' : 'border-gray-200 hover:border-gray-300' }}">
        <input type="radio" name="ui_theme" value="modern" class="mt-1 text-emerald-600 focus:ring-emerald-500" {{ in_array(old('ui_theme', $user->ui_theme === 'custom' ? 'modern' : $user->ui_theme), ['modern', 'custom']) ? 'checked' : '' }}>
        <span>
            <span class="block text-sm font-semibold text-gray-900">{{ platform_brand('name') }} Modern</span>
            <span class="mt-0.5 block text-xs text-gray-500">Navy sidebar, green accents, analytics dashboard.</span>
        </span>
    </label>
</div>
