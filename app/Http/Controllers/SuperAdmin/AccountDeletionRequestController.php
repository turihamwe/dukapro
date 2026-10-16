<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use Illuminate\Http\Request;

class AccountDeletionRequestController extends Controller
{
    public function index()
    {
        $requests = AccountDeletionRequest::query()
            ->with(['user', 'business'])
            ->latest()
            ->paginate(30);

        $newCount = AccountDeletionRequest::query()
            ->where('status', AccountDeletionRequest::STATUS_NEW)
            ->count();

        return view('superadmin.account-deletion-requests.index', [
            'requests' => $requests,
            'newCount' => $newCount,
        ]);
    }

    public function review(AccountDeletionRequest $accountDeletionRequest)
    {
        $accountDeletionRequest->status = AccountDeletionRequest::STATUS_REVIEWED;
        $accountDeletionRequest->reviewed_at = now();
        $accountDeletionRequest->save();

        return back()->with('success', 'Request marked as reviewed. The account was not deleted.');
    }
}
