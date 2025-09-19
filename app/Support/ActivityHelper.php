<?php

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu-satunya pintu tulis audit log.
 */
if (! function_exists('log_activity')) {
    function log_activity(
        string $action,
        Model|int|null $subject = null,   // boleh Model (disarankan) atau id (optional)
        array $before = [],
        array $after  = [],
        ?int $schoolId = null,
        ?string $title = null
    ): void {
        ActivityLogger::record(
            $action,
            $subject instanceof Model ? $subject : null,
            $before,
            $after,
            $schoolId,
            $title
        );
    }
}
