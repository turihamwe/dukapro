<?php

namespace App\Http\Controllers;

use App\Helpers\SystemAuditLogger;
use App\Mail\WelcomeOwnerMail;
use App\Models\Business;
use App\Services\TenantRegistrationService;
use App\Support\CashierMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected TenantRegistrationService $registrationService;

    public function __construct(TenantRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function showPortal()
    {
        return view('auth.portal');
    }

    public function showSuperAdminLogin()
    {
        return view('auth.superadmin-login');
    }

    public function superAdminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $user = Auth::user();
        $request->session()->regenerate();

        if (! $user->isSuperAdmin()) {
            Auth::logout();
            return back()->withErrors(['email' => 'This portal is for platform administrators only.']);
        }

        SystemAuditLogger::record('login', 'SuperAdmin login: ' . $user->email, null, $user->id);

        return redirect()->route('superadmin.dashboard');
    }

    public function showBusinessLogin(Business $portal)
    {
        abort_unless($portal->is_active, 404);

        return view('auth.business-login', ['business' => $portal]);
    }

    public function businessLogin(Request $request, Business $portal)
    {
        abort_unless($portal->is_active, 404);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials for this business portal.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Use the platform admin login instead.']);
        }

        if ((int) $user->business_id !== (int) $portal->id) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account does not belong to ' . $portal->name . '.']);
        }

        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }

        CashierMode::disable($request);

        SystemAuditLogger::record(
            'login',
            'User login: ' . $user->email . ' @ ' . $portal->name,
            $portal->id,
            $user->id
        );

        if ($portal->isSubscriptionExpired()) {
            return redirect()->route('subscription.payment');
        }

        session()->flash('welcome_message', 'Welcome to DukaPro store, ' . $portal->name);

        return $this->redirectHome($user);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'currency_symbol' => 'required|string|max:20',
            'currency_position' => 'required|in:prefix,suffix',
        ]);

        $user = $this->registrationService->register($data);
        $user->load('business');

        Auth::login($user);
        $request->session()->regenerate();

        SystemAuditLogger::record(
            'tenant_registered',
            "New business registered: {$user->business->name}",
            $user->business_id,
            $user->id,
            ['business_slug' => $user->business->slug, 'portal_slug' => $user->business->portal_slug]
        );

        return redirect()->route('tenant.dashboard', ['business' => $user->business->slug])
            ->with('success', 'Welcome to DukaPro store, ' . $user->business->name . '! Your portal URL is ' . $user->business->portalLoginUrl());
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $portalUrl = $user && $user->business ? $user->business->portalLoginUrl() : route('portal');

        if ($user) {
            SystemAuditLogger::record(
                'logout',
                'Logout: ' . $user->email,
                $user->business_id,
                $user->id
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($portalUrl);
    }

    protected function redirectHome($user)
    {
        $params = ['business' => $user->business->slug];

        if ($user->isCashier()) {
            return redirect()->route('tenant.pos.index', $params);
        }

        if ($user->can('view-dashboard')) {
            return redirect()->route('tenant.dashboard', $params);
        }

        return redirect()->route('tenant.pos.index', $params);
    }
}
