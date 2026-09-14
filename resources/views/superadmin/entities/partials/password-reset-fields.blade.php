<div class="border-t border-gray-200 pt-4">
    <p class="text-sm font-medium text-gray-900">Reset password</p>
    <p class="mt-1 text-xs text-gray-500">Leave blank to keep the current password.</p>
    <div class="mt-3 space-y-3">
        <x-password-input
            name="password"
            autocomplete="new-password"
            placeholder="New password (min. 8 characters)"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
        />
        <x-password-input
            name="password_confirmation"
            autocomplete="new-password"
            placeholder="Confirm new password"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
        />
    </div>
</div>
