<?php

namespace App\Http\Controllers\Affiliate;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AffiliateFieldFeedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $affiliate = $request->user()->affiliateProfile;
        abort_unless($affiliate, 404);

        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(AffiliateFieldFeedback::categories()))],
            'merchant_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'summary' => 'required|string|max:200',
            'message' => 'required|string|max:3000',
            'contact_phone' => 'nullable|string|max:30',
        ], [
            'category.required' => 'Please choose what type of issue this is.',
            'category.in' => 'Please choose a valid issue type from the list.',
            'summary.required' => 'Please add a short headline so we understand the issue quickly.',
            'summary.max' => 'Please shorten the headline to one line (200 characters or less).',
            'message.required' => 'Please tell us what the merchant said or what you saw in the field.',
            'message.max' => 'Please keep the details under 3,000 characters.',
        ]);

        $feedback = AffiliateFieldFeedback::create([
            'affiliate_id' => $affiliate->id,
            'user_id' => $request->user()->id,
            'category' => $data['category'],
            'merchant_name' => $data['merchant_name'] ?? null,
            'location' => $data['location'] ?? null,
            'summary' => $data['summary'],
            'message' => $data['message'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => AffiliateFieldFeedback::STATUS_NEW,
        ]);

        SystemAuditLogger::record(
            'affiliate_field_feedback',
            'Field feedback from ' . $affiliate->name . ': ' . $data['summary'],
            null,
            $request->user()->id,
            [
                'feedback_id' => $feedback->id,
                'affiliate_id' => $affiliate->id,
                'category' => $data['category'],
            ]
        );

        return back()->with('success', 'Thank you — your field report was sent to the DukaPro team. We will follow up if needed.');
    }
}
