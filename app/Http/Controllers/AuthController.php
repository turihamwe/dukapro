<?php

namespace App\Http\Controllers;

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

    public function showLogin()
    {
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
        ]);

        $user = $this->registrationService->register($data);

        Auth::login($user);
        $request->session()->regenerate();

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

            if (! $user->business_id) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account is not linked to a business.']);
            }

            if ($user->business->isSubscriptionExpired()) {
                return redirect()->route('subscription.payment');
            }

            return redirect()->intended($this->homeFor($user));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function homeFor($user): string
    {
        $slug = $user->business->slug;

        if ($user->isCashier()) {
            return route('tenant.pos.index', ['business' => $slug]);
        }

        if ($user->isManager() || $user->isOwner()) {
            return route('tenant.dashboard', ['business' => $slug]);
        }

        return route('tenant.pos.index', ['business' => $slug]);
    }
}
