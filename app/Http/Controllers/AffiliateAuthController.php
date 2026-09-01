<?php

namespace App\Http\Controllers;

use App\Services\AuthLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffiliateAuthController extends Controller
{
    protected AuthLoginService $authLoginService;

    public function __construct(AuthLoginService $authLoginService)
    {
        $this->authLoginService = $authLoginService;
    }

    public function showLogin()
    {
        $user = auth()->user();
        if ($user && $user->isAffiliate()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('auth.affiliate-login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        $user = $this->authLoginService->attemptAffiliate(
            $data['login'],
            $data['password'],
            $request->boolean('remember')
        );

        if (! $user) {
            return back()
                ->withErrors(['login' => 'Invalid credentials or this account is not an affiliate.'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->route('affiliate.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login');
    }
}
