<?php

namespace App\Http\Controllers;

use App\Helpers\SystemAuditLogger;
use App\Models\Business;
use App\Services\AuthLoginService;
use App\Services\TenantRegistrationService;
use App\Services\UsernameService;
use App\Support\CashierMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected TenantRegistrationService $registrationService;

    protected AuthLoginService $authLoginService;

    protected UsernameService $usernameService;

    public function __construct(
        TenantRegistrationService $registrationService,
        AuthLoginService $authLoginService,
        UsernameService $usernameService
    ) {
        $this->registrationService = $registrationService;
        $this->authLoginService = $authLoginService;
        $this->usernameService = $usernameService;
    }

    public function showPortal()
    {
        return view('auth.portal');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        $user = $this->authLoginService->attempt(
            $data['login'],
            $data['password'],
            $request->boolean('remember')
        );

        if (! $user) {
            return back()
                ->withErrors(['login' => 'Invalid username, email, or password.'])
                ->onlyInput('login');
        }

        return $this->completeTenantLogin($request, $user, $user->business);
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

    public function showBusinessLogin(string $portal)
    {
        $business = Business::where('portal_slug', $portal)->first();

        if (! $business) {
            return redirect()
                ->route('login')
                ->withInput(['login' => $portal])
                ->withErrors([
                    'portal_slug' => 'That portal ID was not found. You can sign in below with your username or email instead.',
                ]);
        }

        abort_unless($business->is_active, 404);

        return view('auth.business-login', ['business' => $business]);
    }

    public function businessLogin(Request $request, string $portal)
    {
        $business = Business::where('portal_slug', $portal)->first();

        if (! $business) {
            return redirect()
                ->route('login')
                ->withErrors(['login' => 'That business portal was not found. Sign in below with your username or email.']);
        }

        abort_unless($business->is_active, 404);

        $data = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        $user = $this->authLoginService->attempt(
            $data['login'],
            $data['password'],
            $request->boolean('remember'),
            $business
        );

        if (! $user) {
            return back()
                ->withErrors(['login' => 'Invalid credentials for ' . $business->name . '.'])
                ->onlyInput('login');
        }

        return $this->completeTenantLogin($request, $user, $business);
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
        $portalUrl = $user && $user->business ? $user->business->portalLoginUrl() : route('login');

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

    protected function completeTenantLogin(Request $request, $user, Business $business)
    {
        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            Auth::logout();
            return back()->withErrors(['login' => 'Use the platform admin login instead.']);
        }

        if ((int) $user->business_id !== (int) $business->id) {
            Auth::logout();
            return back()->withErrors(['login' => 'This account does not belong to ' . $business->name . '.']);
        }

        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['login' => 'Your account has been deactivated.']);
        }

        CashierMode::disable($request);

        SystemAuditLogger::record(
            'login',
            'User login: ' . $user->email . ' @ ' . $business->name,
            $business->id,
            $user->id
        );

        if ($business->isSubscriptionExpired()) {
            return redirect()->route('subscription.payment');
        }

        session()->flash('welcome_message', 'Welcome to DukaPro store, ' . $business->name);

        return $this->redirectHome($user);
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
