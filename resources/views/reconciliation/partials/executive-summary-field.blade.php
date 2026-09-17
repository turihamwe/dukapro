<div class="rounded-xl border border-indigo-200 bg-indigo-50/70 p-4">
    <label for="executive_summary" class="block text-sm font-semibold text-indigo-950">Executive summary</label>
    <p class="mt-1 text-xs text-indigo-900/70">
        This goes to the owner with your shift report (including WhatsApp share). Review and edit before submitting.
    </p>
    <textarea name="executive_summary" id="executive_summary" rows="4" required
              data-default-summary="{{ e($tradingReport['executive_summary']) }}"
              class="mt-3 w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm leading-relaxed text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ $executiveSummary }}</textarea>
    @error('executive_summary')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-indigo-900/70">
        <span>Business revenue today: <strong class="text-indigo-950">@money($tradingReport['total_revenue'])</strong></span>
        <button type="button" id="reset_executive_summary" class="font-medium text-indigo-700 hover:text-indigo-900">Reset to suggested</button>
    </div>
</div>

<script>
document.getElementById('reset_executive_summary')?.addEventListener('click', function () {
    const field = document.getElementById('executive_summary');
    if (field) {
        field.value = field.dataset.defaultSummary || '';
        field.focus();
    }
});
</script>
