<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminProfileController extends ProfileController
{
    protected string $role = 'admin';

    public function update(Request $request)
    {
        $account = $this->account($request);
        $data = $request->validateWithBag('profile', $this->accountRules($account));
        $account->update($data);

        return redirect()->route('admin.profile.show')->with('success', 'Profile updated successfully.');
    }
}
