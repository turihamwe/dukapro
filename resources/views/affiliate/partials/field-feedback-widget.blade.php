@php
    use App\Models\AffiliateFieldFeedback;

    $affiliate = auth()->user()->affiliateProfile;
    $defaultPhone = old('contact_phone', $affiliate->phone ?? auth()->user()->phone ?? '');
@endphp

<button type="button"
        id="affiliate-field-feedback-tab"
        class="fixed z-[90] flex items-center gap-2 rounded-l-xl border border-r-0 border-emerald-200 bg-emerald-600 px-2 py-3 text-xs font-bold uppercase tracking-wide text-white shadow-lg transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2"
        style="right: 0; top: 50%; transform: translateY(-50%); writing-mode: vertical-rl; text-orientation: mixed;"
        onclick="openAppModal('affiliate-field-feedback-modal')"
        aria-controls="affiliate-field-feedback-modal"
        aria-expanded="false">
    <svg class="h-4 w-4 shrink-0 -rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
    </svg>
    Field feedback
</button>

<div id="affiliate-field-feedback-modal" class="app-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="affiliateFieldFeedbackTitle">
    <div class="app-modal-panel sm:max-w-lg">
        <form method="POST" action="{{ route('affiliate.feedback.store') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <div>
                    <p id="affiliateFieldFeedbackTitle" class="text-lg font-bold text-gray-900">Report field feedback</p>
                    <p class="mt-1 text-sm text-gray-500">Share what merchants tell you — we will pass it to the right team.</p>
                </div>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('affiliate-field-feedback-modal')" aria-label="Close">&times;</button>
            </div>

            <div class="app-modal-body space-y-4">
                @if($errors->any() && old('_feedback_form'))
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                        Please check the highlighted fields below and try again.
                    </div>
                @endif

                <div>
                    <label for="feedback_category" class="mb-1 block text-sm font-medium text-gray-700">What kind of issue is this?</label>
                    <select name="category" id="feedback_category" required
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Choose one…</option>
                        @foreach(AffiliateFieldFeedback::categories() as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="feedback_merchant" class="mb-1 block text-sm font-medium text-gray-700">Shop or customer (optional)</label>
                        <input type="text" name="merchant_name" id="feedback_merchant" value="{{ old('merchant_name') }}" maxlength="255"
                               placeholder="e.g. Mama Sarah Mini Mart"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="feedback_location" class="mb-1 block text-sm font-medium text-gray-700">Area / town (optional)</label>
                        <input type="text" name="location" id="feedback_location" value="{{ old('location') }}" maxlength="255"
                               placeholder="e.g. Ntinda"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label for="feedback_summary" class="mb-1 block text-sm font-medium text-gray-700">Short headline</label>
                    <input type="text" name="summary" id="feedback_summary" value="{{ old('summary') }}" required maxlength="200"
                           placeholder="e.g. Owner cannot log in after signup"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-gray-500">One line — what happened?</p>
                    @error('summary')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="feedback_message" class="mb-1 block text-sm font-medium text-gray-700">Details</label>
                    <textarea name="message" id="feedback_message" rows="4" required maxlength="3000"
                              placeholder="What did the merchant say? What were they trying to do? Any error messages?"
                              class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="feedback_phone" class="mb-1 block text-sm font-medium text-gray-700">Your phone for follow-up (optional)</label>
                    <input type="tel" name="contact_phone" id="feedback_phone" value="{{ $defaultPhone }}" maxlength="30"
                           placeholder="WhatsApp number"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <input type="hidden" name="_feedback_form" value="1">
            </div>

            <div class="app-modal-footer">
                <button type="button" onclick="closeAppModal('affiliate-field-feedback-modal')"
                        class="min-h-[44px] flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="min-h-[44px] flex-1 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                    Send report
                </button>
            </div>
        </form>
    </div>
</div>

@if($errors->any() && old('_feedback_form'))
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof openAppModal === 'function') {
                openAppModal('affiliate-field-feedback-modal');
            }
        });
    </script>
    @endpush
@endif
