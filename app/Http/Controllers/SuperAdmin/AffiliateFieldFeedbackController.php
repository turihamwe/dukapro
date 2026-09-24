<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateFieldFeedback;
use Illuminate\Http\Request;

class AffiliateFieldFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = AffiliateFieldFeedback::query()
            ->with(['affiliate:id,name,code,email', 'user:id,name,email'])
            ->latest('created_at');

        if ($request->filled('status') && in_array($request->status, ['new', 'reviewed'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($builder) use ($term) {
                $builder->where('summary', 'like', "%{$term}%")
                    ->orWhere('message', 'like', "%{$term}%")
                    ->orWhere('merchant_name', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            });
        }

        $feedback = $query->paginate(25)->withQueryString();

        $summary = [
            'total' => AffiliateFieldFeedback::count(),
            'new' => AffiliateFieldFeedback::where('status', AffiliateFieldFeedback::STATUS_NEW)->count(),
        ];

        return view('superadmin.affiliate-feedback.index', [
            'feedback' => $feedback,
            'summary' => $summary,
            'categories' => AffiliateFieldFeedback::categories(),
        ]);
    }

    public function show(AffiliateFieldFeedback $affiliateFieldFeedback)
    {
        $affiliateFieldFeedback->load(['affiliate', 'user']);

        if ($affiliateFieldFeedback->status === AffiliateFieldFeedback::STATUS_NEW) {
            $affiliateFieldFeedback->update(['status' => AffiliateFieldFeedback::STATUS_REVIEWED]);
        }

        return view('superadmin.affiliate-feedback.show', [
            'item' => $affiliateFieldFeedback,
        ]);
    }
}
