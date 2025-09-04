<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

/**
 * Core writer
 */
function _write_activity(string $action, ?string $entityType=null, ?int $entityId=null, ?array $before=null, ?array $after=null): void
{
    ActivityLog::create([
        'user_id'     => Auth::id(),
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'action'      => $action,
        // biarkan array; Eloquent akan serialize ke JSON jika kolom JSON
        'before'      => $before,
        'after'       => $after,
        'ip'          => request()->ip(),                 // <- sesuai migrasi: ip
        'user_agent'  => (string) request()->userAgent(), // <- sesuai migrasi: user_agent
    ]);
}

/**
 * Nama pendek yang kita pakai belakangan
 */
if (!function_exists('activity')) {
    function activity(string $action, ?string $entityType=null, ?int $entityId=null, ?array $before=null, ?array $after=null): void
    {
        _write_activity($action, $entityType, $entityId, $before, $after);
    }
}

/**
 * Nama lama yang sempat muncul di beberapa contoh
 */
if (!function_exists('activity_log')) {
    function activity_log(string $action, string $entityType, int $entityId, ?array $before=null, ?array $after=null): void
    {
        _write_activity($action, $entityType, $entityId, $before, $after);
    }
}
