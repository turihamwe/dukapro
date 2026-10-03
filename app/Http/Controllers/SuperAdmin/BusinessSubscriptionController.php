<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class BusinessSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $search = trim((string) $request->input('q', ''));

        $businesses = Business::query()
            ->with(['owner'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($outer) use ($like) {
                    $outer->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('users', function ($userQuery) use ($like) {
                            $userQuery->where('role', UserRole::OWNER)
                                ->where(function ($ownerMatch) use ($like) {
                                    $ownerMatch->where('name', 'like', $like)
                                        ->orWhere('email', 'like', $like)
                                        ->orWhere('username', 'like', $like);
                                });
                        });
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $multiOwnerEmails = [];
        if ($search !== '') {
            $emailCounts = [];
            foreach ($businesses as $business) {
                $email = strtolower(trim((string) optional($business->owner)->email));
                if ($email === '') {
                    continue;
                }
                $emailCounts[$email] = ($emailCounts[$email] ?? 0) + 1;
            }
            $multiOwnerEmails = array_keys(array_filter($emailCounts, function ($count) {
                return $count > 1;
            }));
        }

        return view('superadmin.subscriptions.extend', [
            'businesses' => $businesses,
            'search' => $search,
            'multiOwnerEmails' => $multiOwnerEmails,
        ]);
    }

    public function extend(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'days' => 'required|integer|min:1|max:730',
            'mode' => 'required|in:trial,active',
            'note' => 'nullable|string|max:500',
        ]);

        $business = Business::query()->withTrashed()->findOrFail($businessId);

        $before = [
            'subscription_status' => $business->subscription_status,
            'trial_ends_at' => optional($business->trial_ends_at)->toDateTimeString(),
            'subscription_ends_at' => optional($business->subscription_ends_at)->toDateTimeString(),
        ];

        if ($data['mode'] === 'trial') {
            $business->extendTrial((int) $data['days']);
        } else {
            $business->activateSubscription((int) $data['days']);
        }

        $business->refresh();

        $after = [
            'subscription_status' => $business->subscription_status,
            'trial_ends_at' => optional($business->trial_ends_at)->toDateTimeString(),
            'subscription_ends_at' => optional($business->subscription_ends_at)->toDateTimeString(),
        ];

        $modeLabel = $data['mode'] === 'trial' ? 'trial' : 'paid subscription';

        SystemAuditLogger::record(
            'subscription_extended_by_superadmin',
            sprintf(
                'Extended %s for %s by %d days (%s)',
                $modeLabel,
                $business->name,
                (int) $data['days'],
                $data['note'] ?? 'no note'
            ),
            $business->id,
            (int) $request->user()->id,
            [
                'days' => (int) $data['days'],
                'mode' => $data['mode'],
                'before' => $before,
                'after' => $after,
            ]
        );

        return redirect()
            ->route('superadmin.subscriptions.extend.index', ['q' => $request->input('q')])
            ->with('success', sprintf(
                'Added %d days to %s for %s. New status: %s.',
                (int) $data['days'],
                $modeLabel,
                $business->name,
                ucfirst($business->subscription_status)
            ));
    }
}
