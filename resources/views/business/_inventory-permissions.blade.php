@php
    use App\Enums\UserRole;
    use App\Support\BatchMode;
    use App\Support\PermissionRegistry;

    $batchPlatformEnabled = BatchMode::platformEnabled();
    $batchBusinessEnabled = (bool) (($business->settings['batch_mode'] ?? false));
    $branchBatchSettings = $branches ?? collect();
@endphp

@if($batchPlatformEnabled)
    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 sm:p-5">
        <p class="text-sm font-semibold text-sky-950">Batch / lot tracking</p>
        <p class="mt-1 text-xs text-sky-900/80">When enabled, stock top-ups create tracked batches with FIFO costing. When disabled, top-ups increase stock quantities directly.</p>
        <label class="mt-4 flex items-start gap-3">
            <input type="hidden" name="batch_mode" value="0">
            <input type="checkbox" name="batch_mode" value="1" class="mt-1 rounded border-sky-300 text-sky-600 focus:ring-sky-500"
                   @checked(old('batch_mode', $batchBusinessEnabled))>
            <span class="text-sm text-sky-950">Enable batch tracking for this business</span>
        </label>

        @if($branchBatchSettings->isNotEmpty())
            <div class="mt-4 space-y-2 border-t border-sky-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-900/70">Per-branch overrides</p>
                @foreach($branchBatchSettings as $branch)
                    @php $branchBatch = array_key_exists('batch_mode', $branch->settings ?? []) ? (bool) $branch->settings['batch_mode'] : null; @endphp
                    <label class="flex items-center justify-between gap-3 rounded-lg bg-white/70 px-3 py-2 text-sm">
                        <span class="font-medium text-gray-900">{{ $branch->name }}</span>
                        <span class="flex items-center gap-2 text-xs text-gray-600">
                            <select name="branch_batch_mode[{{ $branch->id }}]" class="rounded-lg border-gray-300 text-xs">
                                <option value="" @selected($branchBatch === null)>Use business default</option>
                                <option value="1" @selected($branchBatch === true)>Batch on</option>
                                <option value="0" @selected($branchBatch === false)>Batch off</option>
                            </select>
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
@endif

<div class="rounded-xl border border-violet-200 bg-violet-50 p-4 sm:p-5">
    <p class="text-sm font-semibold text-violet-950">Staff role permissions</p>
    <p class="mt-1 text-xs text-violet-900/80">Override default permissions for roles you have on your team. Owners always have full access.</p>

    @php $rolesToShow = $activeStaffRoles ?? []; @endphp

    @if(empty($rolesToShow))
        <p class="mt-4 rounded-lg border border-violet-200/70 bg-white/60 px-3 py-3 text-xs text-violet-900/80">
            No staff members yet. Add team members under
            <a href="{{ tenant_route('tenant.staff.index') }}" class="font-semibold text-violet-700 underline decoration-violet-300 underline-offset-2 hover:text-violet-900">Staff</a>
            to configure permissions for their roles.
        </p>
    @else
        <div class="mt-4 space-y-4">
            @foreach($rolesToShow as $role)
                <div class="rounded-lg border border-violet-200/70 bg-white/60 p-3">
                    <p class="mb-2 text-sm font-semibold capitalize text-violet-950">{{ UserRole::label($role) }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach(PermissionRegistry::definable() as $permission => $meta)
                            @if(in_array($role, $meta['roles'], true))
                                @php
                                    $checked = old("role_permissions.$role.$permission", $permissionMatrix[$role][$permission] ?? PermissionRegistry::defaultGranted($permission, new \App\Models\User(['role' => $role, 'business_id' => $business->id])));
                                @endphp
                                <label class="flex items-start gap-2 text-xs text-gray-700">
                                    <input type="hidden" name="role_permissions[{{ $role }}][{{ $permission }}]" value="0">
                                    <input type="checkbox" name="role_permissions[{{ $role }}][{{ $permission }}]" value="1"
                                           class="mt-0.5 rounded border-gray-300 text-violet-600"
                                           @checked($checked)>
                                    <span>
                                        <span class="font-medium text-gray-900">{{ $meta['label'] }}</span>
                                        <span class="block text-gray-500">{{ $meta['description'] }}</span>
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
