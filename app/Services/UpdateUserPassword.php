<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UpdateUserPassword
{
    public function handle(Request $request, User $account): void
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        DB::transaction(function () use ($account, $data) {
            $current = User::whereKey($account->id)->lockForUpdate()->firstOrFail();
            if (! Hash::check($data['current_password'], $current->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'The current password is incorrect.',
                ])->errorBag('password');
            }
            $current->password = Hash::make($data['password']);
            $current->save();
            $account->password = $current->password;
        });

        // Keep authentication while rotating the session ID and CSRF token.
        $request->session()->regenerate();
    }
}
