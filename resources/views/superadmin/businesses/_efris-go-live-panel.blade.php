@php
    $readiness = $efrisGoLive ?? null;
@endphp
@if($readiness)
    @php
        $overallLabels = [
            'blocked' => ['Blocked', 'bg-gray-200 text-gray-800'],
            'attention' => ['In progress', 'bg-amber-100 text-amber-900'],
            'sandbox' => ['Sandbox ready', 'bg-sky-100 text-sky-900'],
            'production' => ['Production ready', 'bg-emerald-100 text-emerald-900'],
        ];
        [$overallLabel, $overallClass] = $overallLabels[$readiness['overall']] ?? $overallLabels['attention'];
        $statusIcon = ['pass' => '✓', 'warn' => '!', 'fail' => '✗'];
        $statusClass = [
            'pass' => 'text-emerald-700',
            'warn' => 'text-amber-700',
            'fail' => 'text-red-700',
        ];
    @endphp
    <div class="mt-5 border-t border-emerald-200 pt-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-gray-900">EFRIS go-live readiness</p>
                <p class="mt-1 text-xs text-gray-600">
                    Per-sale opt-in at POS (off by default). Use this checklist before client demos and production cutover.
                </p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $overallClass }}">{{ $overallLabel }}</span>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-emerald-100">
                <div class="h-full rounded-full bg-emerald-600 transition-all" style="width: {{ $readiness['score'] }}%"></div>
            </div>
            <span class="text-sm font-semibold text-gray-900">{{ $readiness['score'] }}%</span>
        </div>

        <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div class="rounded-lg bg-white/80 p-3">
                <dt class="text-xs uppercase text-gray-500">TIN</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $readiness['tin'] ?: '—' }}</dd>
            </div>
            <div class="rounded-lg bg-white/80 p-3">
                <dt class="text-xs uppercase text-gray-500">Environment</dt>
                <dd class="mt-1 font-medium capitalize text-gray-900">{{ $readiness['environment'] }}</dd>
            </div>
            <div class="rounded-lg bg-white/80 p-3">
                <dt class="text-xs uppercase text-gray-500">Catalog (efris_item_code)</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $readiness['catalog']['mapped_percent'] }}%</dd>
                @if(($readiness['catalog']['with_sku'] ?? 0) > ($readiness['catalog']['with_efris_code'] ?? 0))
                    <dd class="mt-0.5 text-xs text-gray-500">{{ $readiness['catalog']['with_sku'] }} with SKU (fallback at submit)</dd>
                @endif
            </div>
            <div class="rounded-lg bg-white/80 p-3">
                <dt class="text-xs uppercase text-gray-500">Fiscal success (90d)</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $readiness['submissions']['success'] }}</dd>
            </div>
        </dl>

        <ul class="mt-4 space-y-2">
            @foreach($readiness['checks'] as $check)
                <li class="flex gap-2 rounded-lg bg-white/80 px-3 py-2 text-sm">
                    <span class="font-bold {{ $statusClass[$check['status']] ?? 'text-gray-600' }}">{{ $statusIcon[$check['status']] ?? '·' }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900">{{ $check['label'] }}</p>
                        @if($check['detail'])
                            <p class="text-xs text-gray-600">{{ $check['detail'] }}</p>
                        @endif
                        @if($check['hint'])
                            <p class="mt-0.5 text-xs text-amber-800">{{ $check['hint'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-4 rounded-lg border border-emerald-200 bg-white p-3 text-xs text-gray-600">
            <p class="font-semibold text-gray-800">Production reminders (WEAF / URA)</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                <li>WEAF production subscription & appointment letter (client ↔ WEAF).</li>
                <li>EFRIS device approved; SSL / thumbprint configured in WEAF portal.</li>
                <li>Goods registered and stocked in URA — DukaPro does not sync stock to WEAF yet.</li>
                <li>Server: <code class="rounded bg-gray-100 px-1">php artisan queue:work</code> if not using sync queue.</li>
            </ul>
            <p class="mt-2">
                WEAF docs:
                <a href="https://efrisapi.weafcompany.com/" target="_blank" rel="noopener" class="font-medium text-emerald-700 underline">efrisapi.weafcompany.com</a>
            </p>
        </div>
    </div>
@endif
