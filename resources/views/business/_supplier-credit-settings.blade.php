@php
    use App\Support\SupplierCreditMode;

    $supplierCreditPlatformEnabled = SupplierCreditMode::platformEnabled();
    $supplierCreditBusinessEnabled = (bool) (($business->settings['supplier_credit_mode'] ?? false));
@endphp

@if($supplierCreditPlatformEnabled)
    <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 sm:p-5">
        <p class="text-sm font-semibold text-teal-950">Supplier credit &amp; accounts payable</p>
        <p class="mt-1 text-xs text-teal-900/80">Track stock received on supplier credit, partial payments, and outstanding balances. Does not affect POS cash sales.</p>
        <label class="mt-4 flex items-start gap-3">
            <input type="hidden" name="supplier_credit_mode" value="0">
            <input type="checkbox" name="supplier_credit_mode" value="1" class="mt-1 rounded border-teal-300 text-teal-600 focus:ring-teal-500"
                   @checked(old('supplier_credit_mode', $supplierCreditBusinessEnabled))>
            <span class="text-sm text-teal-950">Enable supplier credit for this business</span>
        </label>
    </div>
@endif
