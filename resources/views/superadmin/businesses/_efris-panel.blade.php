@php
    use App\Support\EfrisCompliance;

    $efris = $business->efrisSetting;
    $adminUnlocked = (bool) optional($efris)->efris_admin_unlocked;
    $ownerEnabled = (bool) optional($efris)->efris_enabled;
@endphp

@if(EfrisCompliance::globallyEnabled())
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50/60 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900">URA EFRIS compliance</p>
            <p class="mt-1 text-xs text-gray-600">
                Unlock EFRIS for this business so the owner can configure WEAF and turn fiscal receipts on or off.
            </p>
        </div>
        @if($adminUnlocked)
            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Unlocked</span>
        @else
            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Locked</span>
        @endif
    </div>

    <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
        <div>
            <dt class="text-xs uppercase text-gray-500">Owner toggle</dt>
            <dd class="mt-1 font-medium text-gray-900">{{ $ownerEnabled ? 'ON — submitting receipts' : 'OFF' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase text-gray-500">WEAF connection</dt>
            <dd class="mt-1 font-medium text-gray-900">
                @if(optional($efris)->isProvisioned())
                    Connected
                @elseif(optional($efris)->provisioning_status)
                    {{ str_replace('_', ' ', ucfirst($efris->provisioning_status)) }}
                @else
                    Not set up
                @endif
            </dd>
        </div>
    </dl>

    @can('platform-full-access')
        <form method="POST" action="{{ route('superadmin.businesses.efris.unlock', $business->id) }}" class="mt-4">
            @csrf
            <input type="hidden" name="efris_admin_unlocked" value="0">
            <label class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-white p-4">
                <input type="checkbox" name="efris_admin_unlocked" value="1"
                       {{ $adminUnlocked ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Enable / unlock EFRIS feature</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Makes EFRIS visible in the owner&apos;s settings with an on/off toggle. Unchecking hides EFRIS entirely and forces it off.</span>
                </span>
            </label>
            <button type="submit" class="mt-3 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Save EFRIS access
            </button>
        </form>
    @else
        <p class="mt-4 text-xs text-gray-500">Full superadmin access is required to change EFRIS unlock status.</p>
    @endcan
</div>
@endif
