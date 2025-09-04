<?php
public function handle($request, Closure $next)
{
    abort_unless(auth()->check() && (auth()->user()->is_admin ?? false), 403);
    return $next($request);
}
