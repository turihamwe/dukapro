<?php

namespace App\Http\Controllers;

use App\Services\AuthLoginService;
use App\Support\LoginPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareholderAuthController extends Controller
{
    protected AuthLoginService $authLoginService;

    public function __construct(AuthLoginService $authLoginService)
    {
        $this->authLoginService = $authLoginService;
    }

    public function showLogin()
    {
        $user = auth()->user();
        if ($user && $user->isDedicatedShareholderAccount()) {
            return redirect()->route('shareholder.dashboard');
        }

        return view('auth.shareholder-login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        $user = $this->authLoginService->attemptShareholder(
            $data['login'],
            $data['password'],
            $request->boolean('remember')
        );

        if (! $user) {
            return back()
                ->withErrors(['login' => 'Invalid credentials or this account is not a shareholder.'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();
        LoginPortal::set($request, LoginPortal::SHAREHOLDER);

        return redirect()->route('shareholder.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        LoginPortal::clear($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shareholder.login');
    }
}
