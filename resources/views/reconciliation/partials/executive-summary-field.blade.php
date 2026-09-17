<div class="rounded-xl border border-indigo-200 bg-indigo-50/70 p-4">
    <p class="text-sm font-semibold text-indigo-950">Executive summary</p>
    <p class="mt-1 text-xs text-indigo-900/70">
        Auto-generated from today&apos;s sales. This is sent to the owner with your shift report, including WhatsApp share.
    </p>
    <p class="mt-3 rounded-lg border border-indigo-200 bg-white px-3 py-3 text-sm leading-relaxed text-gray-900">{{ $executiveSummary }}</p>
    <p class="mt-3 text-xs text-indigo-900/70">
        Business revenue today: <strong class="text-indigo-950">@money($tradingReport['total_revenue'])</strong>
    </p>
</div>
