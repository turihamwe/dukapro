<?php

namespace App\Http\Controllers;

use App\Helpers\SystemAuditLogger;
use App\Models\Business;
use App\Services\AuthLoginService;
use App\Services\AffiliateReferralService;
use App\Services\TenantRegistrationService;
use App\Support\CashierMode;
use App\Support\LoginPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected TenantRegistrationService $registrationService;

    protected AuthLoginService $authLoginService;

    protected AffiliateReferralService $affiliateReferralService;

    public function __construct(
        TenantRegistrationService $registrationService,
        AuthLoginService $authLoginService,
        AffiliateReferralService $affiliateReferralService
    ) {
        $this->registrationService = $registrationService;
        $this->authLoginService = $authLoginService;
        $this->affiliateReferralService = $affiliateReferralService;
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

        if ($user->isDedicatedAffiliateAccount()) {
            LoginPortal::set($request, LoginPortal::AFFILIATE);

            return redirect()->route('affiliate.dashboard');
        }

        if ($user->isDedicatedShareholderAccount()) {
            LoginPortal::set($request, LoginPortal::SHAREHOLDER);

            return redirect()->route('shareholder.dashboard');
        }

        if ($user->business_id && $user->business) {
            return $this->completeTenantLogin($request, $user, $user->business);
        }

        if ($user->isDedicatedShareholderAccount()) {
            LoginPortal::set($request, LoginPortal::SHAREHOLDER);

            return redirect()->route('shareholder.dashboard');
        }

        return back()
            ->withErrors(['login' => 'Invalid username, email, or password.'])
            ->onlyInput('login');
    }

    public function showSuperAdminLogin()
    {
        return view('auth.superadmin-login');
    }

    public function superAdminLogin(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
        ]);

        $user = $this->authLoginService->attemptPlatformAdmin(
            $data['login'],
            $data['password'],
            $request->boolean('remember')
        );

        if (! $user) {
            return back()
                ->withErrors(['login' => 'Invalid username, email, or password.'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

        SystemAuditLogger::record(
            'login',
            'Platform admin login: ' . $user->email,
            null,
            $user->id
        );

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

    public function showRegister(Request $request)
    {
        $this->affiliateReferralService->captureFromRequest($request);

        return view('auth.register');
    }

    public function checkUsername(Request $request)
    {
        $username = strtolower(trim((string) $request->query('username', '')));

        if ($username === '' || strlen($username) < 3) {
            return response()->json(['available' => false, 'message' => 'Username must be at least 3 characters.']);
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $username)) {
            return response()->json(['available' => false, 'message' => 'Use only letters, numbers, dashes, or underscores.']);
        }

        $taken = \App\Models\User::whereRaw('LOWER(username) = ?', [$username])->exists();

        return response()->json([
            'available' => ! $taken,
            'message' => $taken ? 'That username is already taken.' : 'Username is available.',
        ]);
    }

    public function register(Request $request)
    {
        if ($request->filled('username')) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }

        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|in:' . implode(',', \App\Enums\BusinessType::all()),
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
        ]);

        $sponsor = $this->affiliateReferralService->resolveFromSession($request);

        if ($sponsor) {
            $data['sponsor_id'] = $sponsor->id;
        }

        $user = $this->registrationService->register($data);
        $this->affiliateReferralService->clearSession($request);
        $user->load('business');

        Auth::login($user);
        $request->session()->regenerate();
        LoginPortal::set($request, LoginPortal::BUSINESS);

        SystemAuditLogger::record(
            'tenant_registered',
            "New business registered: {$user->business->name}",
            $user->business_id,
            $user->id,
            ['business_slug' => $user->business->slug, 'portal_slug' => $user->business->portal_slug]
        );

        return redirect()->route('tenant.dashboard', ['business' => $user->business->slug])
            ->with('success', 'Welcome to your ' . platform_brand('name') . ' store, ' . $user->business->name . '! Your portal URL is ' . $user->business->portalLoginUrl());
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $portalUrl = $user && $user->isShareholder() && ! $user->business_id
            ? route('shareholder.login')
            : ($user && $user->isDedicatedAffiliateAccount()
            ? route('affiliate.login')
            : ($user && $user->business ? $user->business->portalLoginUrl() : route('login')));

        if ($user) {
            SystemAuditLogger::record(
                'logout',
                'Logout: ' . $user->email,
                $user->business_id,
                $user->id
            );
        }

        Auth::logout();
        LoginPortal::clear($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($portalUrl);
    }

    protected function completeTenantLogin(Request $request, $user, Business $business)
    {
        $request->session()->regenerate();
        LoginPortal::set($request, LoginPortal::BUSINESS);

        if ($user->isPlatformAdmin()) {
            Auth::logout();
            return back()->withErrors(['login' => 'Use the platform admin login instead.']);
        }

        if ($user->isDedicatedAffiliateAccount()) {
            Auth::logout();
            LoginPortal::clear($request);

            return back()->withErrors(['login' => 'Use the affiliate partner login instead.']);
        }

        if ($user->isShareholder() && ! $user->business_id) {
            Auth::logout();
            LoginPortal::clear($request);
            return back()->withErrors(['login' => 'Use the shareholder login instead.']);
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
            if ($user->can('manage-billing')) {
                return redirect()->route('tenant.dashboard', ['business' => $business->slug]);
            }

            return redirect()->route('subscription.payment');
        }

        session()->flash('welcome_message', 'Welcome to ' . platform_brand('name') . ' store, ' . $business->name);

        return $this->redirectHome($user);
    }

    protected function redirectHome($user)
    {
        $params = ['business' => $user->business->slug];

        if ($user->isWaiter() && $user->can('access-waiter-orders')) {
            return redirect()->route('tenant.waiter-orders.index', $params);
        }

        if ($user->isChef() && $user->can('access-kitchen')) {
            return redirect()->route('tenant.kitchen.index', $params);
        }

        if ($user->isCashier()) {
            return redirect()->route('tenant.pos.index', $params);
        }

        if ($user->can('view-dashboard')) {
            return redirect()->route('tenant.dashboard', $params);
        }

        if ($user->can('access-kitchen')) {
            return redirect()->route('tenant.kitchen.index', $params);
        }

        return redirect()->route('tenant.pos.index', $params);
    }
}
