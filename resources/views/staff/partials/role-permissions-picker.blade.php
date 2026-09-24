@php
    use App\Enums\UserRole;
    use App\Support\PermissionRegistry;

    $roles = $roles ?? [];
    $selectedRole = old('role', $selectedRole ?? ($roles[0] ?? null));
@endphp

<div class="rounded-xl border border-violet-200 bg-violet-50 p-4 sm:p-5">
    <p class="text-sm font-semibold text-violet-950">Permissions for this role</p>
    <p class="mt-1 text-xs text-violet-900/80">
        Set what this role can do in your shop. These apply to everyone with the same role — you can fine-tune later under
        <span class="font-medium">Settings</span>.
    </p>

    <div class="mt-4 space-y-4" id="staff-role-permissions-panels">
        @foreach($roles as $role)
            @php
                $applicable = collect(PermissionRegistry::definable())->filter(fn ($meta) => in_array($role, $meta['roles'], true));
            @endphp
            <div class="staff-role-permissions-panel rounded-lg border border-violet-200/70 bg-white/60 p-3 {{ $selectedRole === $role ? '' : 'hidden' }}"
                 data-staff-role="{{ $role }}">
                <p class="mb-2 text-sm font-semibold text-violet-950">{{ UserRole::label($role) }}</p>
                @if($applicable->isEmpty())
                    <p class="text-xs text-gray-500">No configurable permissions for this role.</p>
                @else
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($applicable as $permission => $meta)
                            @php
                                $checked = old(
                                    "role_permissions.$role.$permission",
                                    $permissionMatrix[$role][$permission] ?? PermissionRegistry::defaultGranted($permission, new \App\Models\User(['role' => $role, 'business_id' => $business->id]))
                                );
                            @endphp
                            <label class="flex items-start gap-2 text-xs text-gray-700">
                                <input type="hidden" name="role_permissions[{{ $role }}][{{ $permission }}]" value="0">
                                <input type="checkbox"
                                       name="role_permissions[{{ $role }}][{{ $permission }}]"
                                       value="1"
                                       class="mt-0.5 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                       @checked($checked)>
                                <span>
                                    <span class="font-medium text-gray-900">{{ $meta['label'] }}</span>
                                    <span class="block text-gray-500">{{ $meta['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
