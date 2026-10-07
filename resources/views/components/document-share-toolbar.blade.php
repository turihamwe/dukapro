@props([
    'printHref' => null,
    'printLabel' => 'Print',
    'whatsAppHref' => null,
    'whatsAppMessage' => null,
    'defaultPhone' => null,
    'showPhoneInput' => false,
    'emailHref' => null,
    'layout' => 'inline',
    'phoneInputId' => null,
    'whatsAppLinkId' => null,
    'showPrint' => true,
])

@php
    $phoneId = $phoneInputId ?? ('doc-share-phone-' . substr(md5(serialize($attributes->getAttributes())), 0, 8));
    $waLinkId = $whatsAppLinkId ?? ('doc-share-wa-' . substr(md5($phoneId), 0, 8));
    $wrapClass = $layout === 'panel'
        ? 'no-print mx-auto mt-8 max-w-md rounded-xl border border-gray-200 bg-gray-50 p-4'
        : 'flex flex-wrap items-center gap-2';
    $btn = 'inline-flex shrink-0 items-center justify-center rounded-lg px-3 py-1.5 text-xs font-medium shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1';
    $btnSecondary = $btn . ' border border-gray-300 bg-white text-gray-700 hover:bg-gray-50';
    $btnPrimary = $btn . ' bg-indigo-600 text-white hover:bg-indigo-700';
    $hasShare = ($emailHref || $whatsAppHref || $whatsAppMessage);
    $shareMenuClass = $layout === 'panel'
        ? 'mt-2 flex flex-col gap-1.5 sm:flex-row sm:flex-wrap'
        : 'absolute right-0 z-30 mt-1 min-w-[9.5rem] rounded-lg border border-gray-200 bg-white py-1 shadow-lg';
    $shareLinkClass = 'block w-full px-3 py-2 text-left text-xs font-medium text-gray-800 hover:bg-gray-50 sm:w-auto sm:rounded-md';
@endphp

<div {{ $attributes->merge(['class' => $wrapClass]) }}>
    @if($layout === 'panel')
        <p class="text-sm font-semibold text-gray-900">Print or share</p>
        <p class="mt-1 text-xs text-gray-500">Use <strong>Share</strong> to send via WhatsApp or email.</p>
    @endif

    @if($showPhoneInput)
        <div class="{{ $layout === 'panel' ? 'mt-3' : 'w-full min-w-[10rem] basis-full sm:basis-auto' }}">
            <label for="{{ $phoneId }}" class="mb-1 block text-xs font-medium text-gray-700">WhatsApp number (optional)</label>
            <input type="tel" id="{{ $phoneId }}" value="{{ $defaultPhone }}"
                   placeholder="e.g. 0700123456"
                   class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
        </div>
    @endif

    <div class="{{ $layout === 'panel' ? 'mt-3 flex w-full flex-wrap items-center gap-2' : 'flex flex-wrap items-center gap-2' }}">
        @if($showPrint)
            @if($printHref)
                <a href="{{ $printHref }}" target="_blank" rel="noopener" class="{{ $btnSecondary }}">
                    {{ $printLabel }}
                </a>
            @else
                <button type="button" onclick="window.print()" class="{{ $btnSecondary }}">
                    {{ $printLabel }}
                </button>
            @endif
        @endif

        @if($hasShare)
            <details class="group relative doc-share-menu">
                <summary class="{{ $btnPrimary }} cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                    Share
                </summary>
                <div class="{{ $shareMenuClass }}">
                    <a id="{{ $waLinkId }}" href="{{ $whatsAppHref ?? '#' }}" target="_blank" rel="noopener noreferrer"
                       class="{{ $shareLinkClass }} text-[#128C7E] hover:bg-emerald-50">
                        WhatsApp
                    </a>
                    @if($emailHref)
                        <a href="{{ $emailHref }}" class="{{ $shareLinkClass }} text-sky-800 hover:bg-sky-50">
                            Email
                        </a>
                    @endif
                </div>
            </details>
        @endif
    </div>

    {{ $slot }}
</div>

@if($whatsAppMessage)
    @push('scripts')
    <script>
    (function () {
        var phoneInput = document.getElementById(@json($phoneId));
        var waLink = document.getElementById(@json($waLinkId));
        if (!waLink) return;
        var message = @json($whatsAppMessage);
        var initialHref = @json($whatsAppHref);

        function normalizePhone(value) {
            var digits = (value || '').replace(/\D/g, '');
            if (digits.length === 9) return '256' + digits;
            if (digits.length === 10 && digits.charAt(0) === '0') return '256' + digits.slice(1);
            return digits;
        }

        function updateWhatsAppLink() {
            var digits = phoneInput ? normalizePhone(phoneInput.value) : '';
            var base = digits ? 'https://wa.me/' + digits : 'https://wa.me/';
            waLink.href = base + '?text=' + encodeURIComponent(message);
        }

        if (phoneInput) {
            phoneInput.addEventListener('input', updateWhatsAppLink);
            updateWhatsAppLink();
        } else if (initialHref) {
            waLink.href = initialHref;
        }
    })();
    </script>
    @endpush
@endif
