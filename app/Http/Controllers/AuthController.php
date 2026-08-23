<?php

namespace App\Http\Controllers;

use App\Helpers\SystemAuditLogger;
use App\Services\TenantRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected TenantRegistrationService $registrationService;

    public function __construct(TenantRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function showLogin(Request $request)
    {
        $intended = $request->session()->get('url.intended');

        if ($intended && parse_url($intended, PHP_URL_HOST) !== $request->getHost()) {
            $request->session()->forget('url.intended');
        }

        return view('auth.login');
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
            ['business_slug' => $user->business->slug]
        );

        return redirect()->route('tenant.dashboard', ['business' => $user->business->slug])
            ->with('success', 'Welcome! Your 30-day trial has started.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->isSuperAdmin()) {
                SystemAuditLogger::record('login', 'SuperAdmin login: ' . $user->email, null, $user->id);
                $request->session()->forget('url.intended');

                return redirect()->route('superadmin.dashboard');
            }

            if (! $user->business_id) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account is not linked to a business.']);
            }

            SystemAuditLogger::record(
                'login',
                'User login: ' . $user->email . ' @ ' . $user->business->name,
                $user->business_id,
                $user->id
            );

            if ($user->business->isSubscriptionExpired()) {
                return redirect()->route('subscription.payment');
            }

            $request->session()->forget('url.intended');

            return $this->redirectHome($user);
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

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

        return redirect()->route('login');
    }

    protected function redirectHome($user)
    {
        $params = ['business' => $user->business->slug];

        if ($user->isCashier()) {
            return redirect()->route('tenant.pos.index', $params);
        }

        if ($user->isManager() || $user->isOwner()) {
            return redirect()->route('tenant.dashboard', $params);
        }

        return redirect()->route('tenant.pos.index', $params);
    }
}
