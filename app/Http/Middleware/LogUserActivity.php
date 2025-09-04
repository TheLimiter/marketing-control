<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (Auth::check()) {
                ActivityLog::create([
                    'user_id'    => Auth::id(),
                    'method'     => $request->method(),
                    'path'       => $request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'status'     => $response->getStatusCode(),
                    'payload'    => json_encode($this->safePayload($request), JSON_UNESCAPED_UNICODE),
                ]);
            }
        } catch (\Throwable $e) {
            // don't break the app if logging fails
        }

        return $response;
    }

    /**
     * Filter out sensitive fields from request payload.
     */
    protected function safePayload(Request $request): array
    {
        $input = $request->all();
        $sensitive = ['password', 'password_confirmation', 'current_password', '_token'];
        foreach ($sensitive as $key) {
            if (array_key_exists($key, $input)) {
                $input[$key] = '***';
            }
        }
        return $input;
    }
}
