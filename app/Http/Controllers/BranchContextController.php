<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Support\BranchImpersonation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchContextController extends Controller
{
    public function switch(Request $request, Business $business)
    {
        $this->authorize('impersonate-branches');

        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(function ($query) use ($business) {
                    $query->where('business_id', $business->id)->where('is_active', true);
                }),
            ],
        ]);

        $branch = Branch::query()->findOrFail((int) $data['branch_id']);

        BranchImpersonation::enter($request->user(), $branch);

        return redirect()
            ->back()
            ->with('success', 'Operating as ' . $branch->name . '.');
    }

    public function clear(Request $request, Business $business)
    {
        $this->authorize('impersonate-branches');

        BranchImpersonation::leave();

        return redirect()
            ->back()
            ->with('success', 'Returned to all-branch view.');
    }
}
