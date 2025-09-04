<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RememberFilters
{
    public function handle(Request $request, Closure $next)
    {
        // Jika ada request untuk reset filter
        if ($request->has('reset')) {
            session()->forget('filters.'.$request->path());
            return redirect()->to($request->url()); // buang ?reset=1 dari URL
        }

        // hanya berlaku untuk halaman aktivitas
        if ($request->is('aktivitas*') || $request->is('master-sekolah/*/aktivitas*')) {
            // simpan filter ke session
            if ($request->query()) {
                session(['filters.'.$request->path() => $request->query()]);
            } else {
                // kalau tidak ada query tapi ada filter tersimpan, merge
                if (session()->has('filters.'.$request->path())) {
                    return redirect()->to($request->url().'?'.http_build_query(session('filters.'.$request->path())));
                }
            }
        }

        return $next($request);
    }
}
