<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // izinkan hanya halaman ganti password & logout
            if (! $request->routeIs('account.password.*') && ! $request->routeIs('logout')) {
                return redirect()->route('account.password.edit')
                    ->with('warn', 'Silakan ganti password terlebih dahulu.');
            }
        }
        return $next($request);
    }
}
