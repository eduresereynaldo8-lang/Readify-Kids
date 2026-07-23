<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;

class AuthController extends Controller
{
    // Show login page
    public function showLogin()
    {
        return view('auth.login');
    }

    // Handle login
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['username' => 'Invalid username or password.']);
        }

        Auth::login($user);

        if ($user->role === 'teacher') {
            return redirect()->route('teacher.dashboard');
        }

        return redirect()->route('student.dashboard');
    }

    // Show teacher registration page
    public function showTeacherRegister()
    {
        return view('auth.register_teacher');
    }

    // Handle teacher registration
    public function registerTeacher(Request $request)
    {
        $request->validate([
            'firstname'   => 'required|string|max:100',
            'lastname'    => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'username'    => 'required|string|unique:users,username',
            'school_name' => 'required|string|max:255',
            'password'    => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'teacher',
            'status'   => 'active',
        ]);

        Teacher::create([
            'user_id'     => $user->id,
            'firstname'   => $request->firstname,
            'lastname'    => $request->lastname,
            'school_name' => $request->school_name,
        ]);

        Auth::login($user);

        return redirect()->route('teacher.dashboard');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}