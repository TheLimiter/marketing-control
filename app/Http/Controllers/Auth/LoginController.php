<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
            'remember' => ['nullable','boolean'],
        ]);

        $remember = $request->boolean('remember');

        // Coba login
        if (! Auth::attempt(['email'=>$cred['email'], 'password'=>$cred['password']], $remember)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Optional guard: user harus aktif
        if (! Auth::user()->active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda nonaktif. Hubungi admin.'])->onlyInput('email');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
