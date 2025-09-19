<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && ! Auth::user()->active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email'=>'Akun Anda nonaktif.']);
        }
        return $next($request);
    }
}
