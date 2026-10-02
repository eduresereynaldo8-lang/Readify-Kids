<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherProfileController extends ProfileController
{
    protected string $role = 'teacher';

    public function update(Request $request)
    {
        $account = $this->account($request);
        $teacher = $account->teacher()->firstOrFail();
        $data = $request->validateWithBag('profile', array_merge($this->accountRules($account), [
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'school_name' => ['required', 'string', 'max:255'],
        ]));

        DB::transaction(function () use ($account, $teacher, $data) {
            $account->update(['username' => $data['username'], 'email' => $data['email'] ?? null]);
            $teacher->update([
                'firstname' => $data['firstname'], 'lastname' => $data['lastname'],
                'school_name' => $data['school_name'],
            ]);
        });

        return redirect()->route('teacher.profile.show')->with('success', 'Profile updated successfully.');
    }
}
