<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountDeletionController extends Controller
{
    public function create(AccountDeletionService $deletion)
    {
        $user = Auth::user();

        return view('legal.account-delete', [
            'user' => $user,
            'refusal' => $user ? $deletion->refusalReason($user) : null,
            'closesShop' => $user ? $deletion->closesShop($user) : false,
        ]);
    }

    public function destroy(Request $request, AccountDeletionService $deletion)
    {
        $data = $request->validate([
            'login' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
            'confirm_shop' => ['nullable', 'accepted'],
        ]);

        $user = Auth::user();
        if (! $user) {
            $login = strtolower(trim((string) ($data['login'] ?? '')));
            if ($login === '') {
                throw ValidationException::withMessages([
                    'login' => 'Enter the email or username on the account.',
                ]);
            }

            $user = User::query()
                ->where(function ($query) use ($login) {
                    $query->where('email', $login)->orWhere('username', $login);
                })
                ->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'login' => 'Those sign-in details do not match an account.',
                ]);
            }
        } elseif (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'That password does not match this account.',
            ]);
        }

        if ($reason = $deletion->refusalReason($user)) {
            throw ValidationException::withMessages(['login' => $reason]);
        }

        if ($deletion->closesShop($user) && ! $request->boolean('confirm_shop')) {
            throw ValidationException::withMessages([
                'confirm_shop' => 'Confirm that the shop should be closed and customer and supplier contact details erased.',
            ]);
        }

        Auth::logout();
        $deletion->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('account.delete.done')
            ->with('account_deleted', true);
    }

    public function done()
    {
        if (! session('account_deleted')) {
            return redirect()->route('account.delete');
        }

        return view('legal.account-delete-done');
    }
}
