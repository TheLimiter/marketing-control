<?php

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

if (!function_exists('log_activity')) {
    function log_activity(string $action, ?Model $subject = null, array $before = [], array $after = [], ?int $schoolId = null, ?string $title = null): void {
        ActivityLogger::record($action, $subject, $before, $after, $schoolId, $title);
    }
}
