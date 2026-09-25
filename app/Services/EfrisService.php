<?php

namespace App\Services;

use App\Models\EfrisSetting;
use App\Models\Sale;
use App\Support\EfrisCompliance;
use App\Support\EfrisItemMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EfrisService
{
    public function generateAccessToken(string $username, string $password, int $expiryDays = 30, string $tokenName = 'DukaPro Integration'): array
    {
        $response = $this->client(null, 'Sandbox')
            ->post('/api/v1/auth/generate-token', [
                'username' => $username,
                'password' => $password,
                'expiry_days' => $expiryDays,
                'token_name' => $tokenName,
            ]);

        return $this->parseResponse($response->json(), 'Token generation failed');
    }

    public function validateAccessToken(string $token): array
    {
        $response = $this->client($token, 'Sandbox')
            ->post('/api/v1/auth/validate-token', [
                'token' => $token,
            ]);

        return $this->parseResponse($response->json(), 'Token validation failed');
    }

    public function submitFiscalReceipt(Sale $sale): array
    {
        $sale->loadMissing(['items', 'business.efrisSetting', 'customer', 'user']);

        if (! EfrisCompliance::globallyEnabled()) {
            throw new RuntimeException('EFRIS is disabled platform-wide.');
        }

        $settings = $sale->business ? $sale->business->efrisSetting : null;

        if (! $settings || ! EfrisCompliance::isAdminUnlocked($settings)) {
            throw new RuntimeException('EFRIS is not unlocked for this business.');
        }

        if (! $settings->efris_enabled) {
            throw new RuntimeException('EFRIS is turned off for this business.');
        }

        if (! $settings->isConfigured()) {
            throw new RuntimeException('EFRIS is not fully configured for this business.');
        }

        if ($sale->is_credit_sale) {
            throw new RuntimeException('Credit sales are not submitted to EFRIS until payment is collected.');
        }

        $payload = $this->buildReceiptPayload($sale, $settings);
        $tin = $settings->resolveTin($sale->business);

        if (! $tin) {
            throw new RuntimeException('Company TIN is missing. Add it on your business profile.');
        }

        $environment = $settings->environmentHeader();

        $response = $this->client($settings->getDecryptedApiToken(), $environment)
            ->post('/api/' . $tin . '/generate-fiscal-receipt?environment=' . urlencode($environment), [
                'data' => $payload,
                'environment' => $environment,
                'deploymentEnvironment' => $environment,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WEAF API HTTP ' . $response->status() . ': ' . $response->body());
        }

        $body = $response->json();

        return $this->extractFiscalDocument($this->parseResponse($body, 'Fiscal receipt submission failed'));
    }

    public function buildReceiptPayload(Sale $sale, EfrisSetting $settings): array
    {
        $business = $sale->business;
        $issuedAt = optional($sale->completed_at)->format('d/m/Y H:i:s') ?: Carbon::now()->format('d/m/Y H:i:s');

        return [
            'sellerDetails' => [
                'referenceNo' => $sale->sale_number,
                'issuedDate' => $issuedAt,
                'branchId' => $settings->efris_branch_id ?: '',
                'remarks' => $sale->notes ?: '',
            ],
            'basicInformation' => [
                'operator' => optional($sale->user)->name ?: ($business->name ?? 'Cashier'),
                'currency' => $business->currency_symbol ?: 'UGX',
                'paymentMode' => $this->mapPaymentMode($sale->payment_method),
                'invoiceIndustryCode' => '101',
                'isPreview' => '0',
                'isRefund' => '0',
            ],
            'buyerDetails' => $this->buildBuyerDetails($sale, $settings),
            'itemsBought' => $this->buildItems($sale),
        ];
    }

    protected function buildBuyerDetails(Sale $sale, EfrisSetting $settings): array
    {
        $defaults = config('efris.default_buyer');
        $customer = $sale->customer;

        if ($customer) {
            return [
                'buyerTin' => $settings->default_buyer_tin ?: ($defaults['tin'] ?? '999999999'),
                'buyerBusinessName' => $customer->company_name ?: $customer->name,
                'buyerLegalName' => $customer->name,
                'buyerType' => '1',
                'buyerAddress' => $customer->address ?: ($defaults['address'] ?? 'N/A'),
                'buyerEmail' => $customer->email ?: ($defaults['email'] ?? 'walkin@example.com'),
                'buyerLinePhone' => $customer->phone ?: ($defaults['line_phone'] ?? '0700000000'),
                'buyerMobilePhone' => $customer->phone ?: ($defaults['mobile_phone'] ?? '0700000000'),
            ];
        }

        return [
            'buyerTin' => $settings->default_buyer_tin ?: ($defaults['tin'] ?? '999999999'),
            'buyerBusinessName' => $defaults['business_name'] ?? 'Walk-in Customer',
            'buyerLegalName' => $defaults['legal_name'] ?? 'Walk-in Customer',
            'buyerType' => $defaults['type'] ?? '1',
            'buyerAddress' => $defaults['address'] ?? 'N/A',
            'buyerEmail' => $defaults['email'] ?? 'walkin@example.com',
            'buyerLinePhone' => $defaults['line_phone'] ?? '0700000000',
            'buyerMobilePhone' => $defaults['mobile_phone'] ?? '0700000000',
        ];
    }

    protected function buildItems(Sale $sale): array
    {
        $sale->loadMissing(['items.product']);
        $items = [];

        foreach ($sale->items as $item) {
            $product = $item->product;
            $quantity = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;
            $total = round((float) $item->subtotal, 2);
            $discount = (float) ($item->discount_amount ?? 0);

            $items[] = [
                'itemCode' => EfrisItemMapper::itemCode($item, $product),
                'itemType' => EfrisItemMapper::itemType($product),
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'total' => $total,
                'unitOfMeasure' => EfrisItemMapper::unitOfMeasure($item, $product),
                'discountFlag' => $discount > 0 ? '1' : '2',
                'discountTotal' => $discount > 0 ? (string) $discount : '',
            ];
        }

        return $items;
    }

    protected function mapPaymentMode(?string $paymentMethod): string
    {
        return config('efris.payment_modes.' . ($paymentMethod ?: 'cash'), '101');
    }

    protected function mapUnit(?string $unit): string
    {
        $key = strtolower((string) $unit);

        return config('efris.unit_map.' . $key, config('efris.default_unit', 'PCE'));
    }

    protected function client(?string $token, string $environment)
    {
        $request = Http::baseUrl(rtrim(config('efris.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->withHeaders([
                'X-Environment' => $environment,
            ]);

        if ($token) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    protected function parseResponse(?array $body, string $fallbackMessage): array
    {
        if (! is_array($body)) {
            throw new RuntimeException($fallbackMessage);
        }

        $returnCode = data_get($body, 'status.returnCode');
        $returnMessage = data_get($body, 'status.returnMessage', $fallbackMessage);

        if (! in_array((string) $returnCode, ['00', '0'], true)) {
            $detail = is_string($body['data'] ?? null)
                ? $body['data']
                : (is_array($body['data'] ?? null) ? json_encode($body['data']) : $returnMessage);

            throw new RuntimeException(trim($returnMessage . ': ' . $detail));
        }

        return is_array($body['data'] ?? null) ? $body['data'] : [];
    }

    protected function extractFiscalDocument(array $data): array
    {
        $basic = $data['basicInformation'] ?? [];
        $summary = $data['summary'] ?? [];

        $fdn = $basic['invoiceNo']
            ?? $basic['fdn']
            ?? $data['invoiceNo']
            ?? null;

        $antifakeCode = $basic['antifakeCode'] ?? $data['antifakeCode'] ?? null;
        $qrCode = $summary['qrCode'] ?? $data['qrCode'] ?? null;

        if (! $fdn && ! $qrCode) {
            Log::warning('EFRIS receipt succeeded but no FDN/QR returned', ['data' => $data]);

            throw new RuntimeException('EFRIS accepted the receipt but did not return a fiscal document number.');
        }

        return [
            'fdn' => $fdn,
            'antifake_code' => $antifakeCode,
            'qr_code' => $qrCode,
            'raw' => $data,
        ];
    }

    public function queueSaleSubmission(Sale $sale): void
    {
        $sale->loadMissing('business.efrisSetting');

        if (! $sale->business || ! $sale->business->usesEfris()) {
            return;
        }

        if ($sale->is_credit_sale) {
            return;
        }

        $sale->update([
            'efris_status' => 'pending',
            'efris_error' => null,
        ]);

        \App\Jobs\SubmitSaleToEfrisJob::dispatch($sale->id);
    }
}
