<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UpdateUserPassword;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

abstract class ProfileController extends Controller
{
    protected string $role;

    protected function account(Request $request): User
    {
        // Legacy teacher middleware also admits admins; self-service is role-specific.
        abort_unless($request->user()?->role === $this->role, 403);

        return $request->user();
    }

    public function show(Request $request)
    {
        $account = $this->account($request);
        $person = match ($this->role) {
            'teacher' => $account->teacher()->firstOrFail(),
            'student' => $account->student()->firstOrFail(),
            default => $account,
        };

        return view('profile.show', [
            'account' => $account, 'person' => $person, 'profileRole' => $this->role,
        ]);
    }

    protected function accountRules(User $account): array
    {
        return [
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($account->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($account->id)],
        ];
    }

    public function password(Request $request, UpdateUserPassword $updatePassword)
    {
        $updatePassword->handle($request, $this->account($request));

        return redirect()->route($this->role.'.profile.show')
            ->with('success', 'Password changed successfully.');
    }
}
