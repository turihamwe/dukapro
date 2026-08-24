<?php

namespace App\Http\Controllers;

use App\Enums\DamageReason;
use App\Models\Damage;
use App\Models\Product;
use App\Services\DamageService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DamageController extends Controller
{
    protected DamageService $damageService;

    public function __construct(DamageService $damageService)
    {
        $this->damageService = $damageService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Damage::class);
        $date = $request->get('date', Carbon::today()->toDateString());

        $query = Damage::with('product', 'user')
            ->whereDate('damage_date', $date)
            ->latest('created_at');

        $damages = $query->paginate(20)->withQueryString();
        $summary = $this->damageService->summarizeForDate(
            $request->user()->business_id,
            Carbon::parse($date)
        );

        $products = Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'measurement_unit']);

        $reasons = DamageReason::labels();

        return view('damages.index', compact('damages', 'summary', 'date', 'products', 'reasons'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Damage::class);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|in:' . implode(',', DamageReason::all()),
            'damage_date' => 'nullable|date',
        ]);

        $data['damage_date'] = $data['damage_date'] ?? Carbon::today()->toDateString();

        $this->damageService->record($request->user(), $data);

        return redirect()->to(tenant_route('tenant.damages.index', ['date' => $data['damage_date']]))
            ->with('success', 'Damage recorded and stock updated.');
    }
}
