<?php

namespace App\Http\Controllers;

use App\Enums\DamageReason;
use App\Models\Damage;
use App\Models\Product;
use App\Services\DamageService;
use App\Services\ProductBatchService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DamageController extends Controller
{
    protected DamageService $damageService;

    protected ProductBatchService $batchService;

    public function __construct(DamageService $damageService, ProductBatchService $batchService)
    {
        $this->damageService = $damageService;
        $this->batchService = $batchService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Damage::class);
        $date = $request->get('date', Carbon::today()->toDateString());

        $damages = Damage::with('product', 'user')
            ->whereDate('damage_date', $date)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = $this->damageService->summarizeForDate(
            $request->user()->business_id,
            Carbon::parse($date)
        );

        $products = Product::sellable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'measurement_unit', 'business_id'])
            ->filter(fn (Product $product) => $this->batchService->availableStock($product) > 0)
            ->map(function (Product $product) {
                $product->setAttribute('available_stock', $this->batchService->availableStock($product));

                return $product;
            })
            ->values();

        $reasons = DamageReason::labels();
        $businessId = (int) $request->user()->business_id;

        return view('damages.index', compact('damages', 'summary', 'date', 'products', 'reasons', 'businessId'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Damage::class);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'reason' => 'required|in:' . implode(',', DamageReason::all()),
            'damage_date' => 'nullable|date',
        ]);

        $data['damage_date'] = $data['damage_date'] ?? Carbon::today()->toDateString();

        $this->damageService->record($request->user(), $data);

        if ($request->user()->usesCashierExperience()) {
            return redirect()->to(tenant_route('tenant.damages.index', ['date' => $data['damage_date']]))
                ->with('success', 'Damage recorded and stock updated.');
        }

        return redirect()->to(tenant_route('tenant.damages.index', ['date' => $data['damage_date']]))
            ->with('success', 'Damage recorded and stock updated.');
    }
}
