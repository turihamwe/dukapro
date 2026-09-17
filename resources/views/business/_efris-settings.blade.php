@php
    use App\Services\WeafAccountProvisioner;
    use App\Support\EfrisCompliance;
    use App\Support\WeafPlatformCredentials;

    $efris = $efrisSetting ?? null;
    $adminUnlocked = EfrisCompliance::isAdminUnlocked($efris);
@endphp

@if(! $adminUnlocked)
    {{-- Hidden until platform "Use EFRIS" is on and a superadmin unlocks this business. --}}
@else
@php
    $efrisEnabled = (bool) old('efris.enabled', optional($efris)->efris_enabled ?? false);
    $businessTin = preg_replace('/\D+/', '', (string) old('tax_number', $business->tax_number ?? ''));
    $provisionStatus = optional($efris)->provisioning_status ?? WeafAccountProvisioner::STATUS_NOT_STARTED;
    $isConnected = optional($efris)->isProvisioned();
    $autoProvision = WeafPlatformCredentials::autoProvisionEnabled();
    $statusLabels = [
        WeafAccountProvisioner::STATUS_CONNECTED => ['Connected', 'bg-emerald-100 text-emerald-800'],
        WeafAccountProvisioner::STATUS_COMPANY_PENDING => ['Company linking pending', 'bg-amber-100 text-amber-800'],
        WeafAccountProvisioner::STATUS_TOKEN_READY => ['Token ready', 'bg-sky-100 text-sky-800'],
        WeafAccountProvisioner::STATUS_EMAIL_VERIFICATION => ['Verify email', 'bg-violet-100 text-violet-800'],
        WeafAccountProvisioner::STATUS_REGISTERED => ['Account created', 'bg-sky-100 text-sky-800'],
        WeafAccountProvisioner::STATUS_FAILED => ['Connection failed', 'bg-red-100 text-red-800'],
    ];
    [$statusLabel, $statusClass] = $statusLabels[$provisionStatus] ?? ['Not connected', 'bg-gray-100 text-gray-700'];
@endphp

<div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 sm:p-5">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900">URA EFRIS (WEAF)</p>
            <p class="mt-1 text-xs text-gray-600">
                Submit fiscal receipts to Uganda Revenue Authority via WEAF when a sale completes.
            </p>
        </div>
        @if($isConnected)
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
        @endif
    </div>

    @if(session('efris_connect_result.message'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-white p-3 text-sm text-emerald-900">
            {{ session('efris_connect_result.message') }}
        </div>
    @endif

    @error('efris_connect')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @if($autoProvision)
        <div class="mb-4 rounded-lg border border-emerald-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-900">One-click WEAF setup</p>
            <p class="mt-1 text-xs text-gray-600">
                Uses your business email
                @if($business->email)
                    (<span class="font-medium">{{ $business->email }}</span>)
                @else
                    — add a business email above first
                @endif
                and TIN from your profile
                @if($businessTin !== '')
                    (<span class="font-medium">{{ $businessTin }}</span>)
                @else
                    — add your TIN under Tax / registration number first
                @endif.
            </p>

            @if(optional($efris)->provisioning_error)
                <p class="mt-2 text-xs text-amber-800">{{ $efris->provisioning_error }}</p>
            @endif

            <form method="POST" action="{{ tenant_route('tenant.business.efris.connect') }}" class="mt-4 space-y-3">
                @csrf
                <x-input type="password" name="weaf_password" label="WEAF password (only if you already have a WEAF account)"
                         placeholder="Leave blank for automatic registration"
                         autocomplete="new-password" />
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    {{ $isConnected ? 'Reconnect EFRIS' : 'Connect EFRIS' }}
                </button>
            </form>

            @if(optional($efris)->weaf_email && $isConnected)
                <p class="mt-3 text-xs text-gray-500">
                    WEAF account: {{ $efris->weaf_email }}
                    @if($efris->weaf_token_expires_at)
                        · Token expires {{ $efris->weaf_token_expires_at->format('M j, Y') }}
                    @endif
                </p>
            @endif
        </div>
    @endif

    <label class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-white p-4">
        <input type="hidden" name="efris[enabled]" value="0">
        <input type="checkbox" name="efris[enabled]" value="1" id="efrisEnabled"
               {{ $efrisEnabled ? 'checked' : '' }}
               class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
        <span>
            <span class="block text-sm font-medium text-gray-900">Enable EFRIS fiscal receipts</span>
            <span class="mt-0.5 block text-xs text-gray-500">When ON, each completed sale is sent to WEAF for a fiscal document number (FDN) and QR code.</span>
        </span>
    </label>

    <div id="efrisFields" class="mt-4 space-y-4 {{ $efrisEnabled ? '' : 'hidden' }}">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Company TIN</label>
                <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900">
                    {{ $businessTin !== '' ? $businessTin : 'Not set — update Tax / registration number above' }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Taken from your business profile. Save the profile after changing it.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Environment</label>
                <select name="efris[environment]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach(config('efris.environments') as $key => $label)
                        <option value="{{ $key }}" @selected(old('efris.environment', optional($efris)->efris_environment ?: 'sandbox') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="text" name="efris[branch_id]" label="EFRIS branch ID (optional)" value="{{ old('efris.branch_id', optional($efris)->efris_branch_id) }}" />
            <x-input type="text" name="efris[default_buyer_tin]" label="Default walk-in buyer TIN" value="{{ old('efris.default_buyer_tin', optional($efris)->default_buyer_tin) }}" placeholder="{{ config('efris.default_buyer.tin') }}" />
        </div>

        <details class="rounded-lg border border-gray-200 bg-white p-4">
            <summary class="cursor-pointer text-sm font-medium text-gray-900">Advanced: manual WEAF API token</summary>
            <div class="mt-3">
                <x-input type="password" name="efris[api_token]" label="WEAF API token"
                         placeholder="{{ optional($efris)->hasStoredToken() ? '•••••••• (leave blank to keep current)' : 'Only if not using Connect EFRIS' }}"
                         autocomplete="new-password" />
                <p class="mt-2 text-xs text-gray-500">Most businesses should use Connect EFRIS above instead of pasting a token manually.</p>
            </div>
        </details>

        <p class="text-xs text-gray-500">
            Products must exist in EFRIS (sync via WEAF) — item SKU or name is sent as the item code.
            Sandbox testing is free; production requires an active WEAF subscription.
        </p>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var toggle = document.getElementById('efrisEnabled');
    var fields = document.getElementById('efrisFields');
    if (!toggle || !fields) return;
    toggle.addEventListener('change', function () {
        fields.classList.toggle('hidden', !toggle.checked);
    });
})();
</script>
@endpush
@endif
