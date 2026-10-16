<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
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
            'reasons' => $this->leavingReasons(),
        ]);
    }

    public function destroy(Request $request, AccountDeletionService $deletion)
    {
        $reasons = $this->leavingReasons();

        $data = $request->validate([
            'login' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'reason' => ['required', 'string', 'in:'.implode(',', array_keys($reasons))],
            'details' => ['nullable', 'string', 'max:2000', 'required_if:reason,other'],
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

        $already = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', AccountDeletionRequest::STATUS_NEW)
            ->exists();

        if (! $already) {
            AccountDeletionRequest::query()->create([
                'user_id' => $user->id,
                'business_id' => $user->business_id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'phone' => $user->phone,
                'role' => $user->role,
                'message' => $data['reason'] === 'other'
                    ? trim($data['details'])
                    : $reasons[$data['reason']],
                'status' => AccountDeletionRequest::STATUS_NEW,
            ]);
        }

        return redirect()
            ->route('account.delete.done')
            ->with('account_deletion_requested', true);
    }

    public function done()
    {
        if (! session('account_deletion_requested')) {
            return redirect()->route('account.delete');
        }

        return view('legal.account-delete-done');
    }

    protected function leavingReasons(): array
    {
        return [
            'no_longer_needed' => 'I no longer need it',
            'hard_to_use' => 'It is hard to use',
            'missing_something' => 'It is missing something I need',
            'too_expensive' => 'It costs too much',
            'switching' => 'I am switching to another product',
            'other' => 'Other',
        ];
    }
}
