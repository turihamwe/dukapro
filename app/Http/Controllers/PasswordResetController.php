<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    protected PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    public function create(Request $request)
    {
        $portal = $this->passwordResetService->normalizePortal($request->query('portal'));
        $business = $this->passwordResetService->findBusinessByPortalSlug($request->query('business'));

        if ($portal === PasswordResetService::PORTAL_BUSINESS && ! $business) {
            return redirect()
                ->route('portal')
                ->with('warning', 'Select a valid business portal before resetting your password.');
        }

        return view('auth.forgot-password', [
            'portal' => $portal,
            'business' => $business,
            'portalLabel' => $this->passwordResetService->portalLabel($portal, $business),
            'loginUrl' => $this->passwordResetService->loginUrl($portal, $business),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'portal' => 'nullable|string|max:50',
            'business' => 'nullable|string|max:100',
        ]);

        $portal = $this->passwordResetService->normalizePortal($data['portal'] ?? null);
        $business = $this->passwordResetService->findBusinessByPortalSlug($data['business'] ?? null);

        if ($portal === PasswordResetService::PORTAL_BUSINESS && ! $business) {
            return back()
                ->withErrors(['email' => 'That business portal could not be found.'])
                ->onlyInput('email');
        }

        $this->passwordResetService->sendResetLink($data['email'], $portal, $business);

        return back()->with(
            'success',
            'If an account exists for that email address, we have sent a password reset link.'
        );
    }

    public function edit(Request $request, string $token)
    {
        $portal = $this->passwordResetService->normalizePortal($request->query('portal'));
        $business = $this->passwordResetService->findBusinessByPortalSlug($request->query('business'));

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
            'portal' => $portal,
            'business' => $business,
            'portalLabel' => $this->passwordResetService->portalLabel($portal, $business),
            'loginUrl' => $this->passwordResetService->loginUrl($portal, $business),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'portal' => 'nullable|string|max:50',
            'business' => 'nullable|string|max:100',
        ]);

        $portal = $this->passwordResetService->normalizePortal($data['portal'] ?? null);
        $business = $this->passwordResetService->findBusinessByPortalSlug($data['business'] ?? null);

        $status = $this->passwordResetService->resetPassword($data, $portal, $business);

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->to($this->passwordResetService->loginUrl($portal, $business))
                ->with('success', __($status));
        }

        return back()
            ->withErrors(['email' => __($status)])
            ->onlyInput('email');
    }
}
