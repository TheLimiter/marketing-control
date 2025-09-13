<?php

use Illuminate\Support\Facades\Log;
// pakai modelmu kalau ada:
use App\Models\ActivityLog;

if (! function_exists('log_activity')) {
    /**
     * Catat activity.
     *
     * @param  string      $type
     * @param  mixed|null  $subject  (Model/ID)
     * @param  array       $before
     * @param  array       $after
     * @param  int|null    $schoolId
     * @param  string|null $message
     */
    function log_activity(
        string $type,
        $subject = null,
        array $before = [],
        array $after = [],
        ?int $schoolId = null,
        ?string $message = null
    ): void {
        // Jika punya tabel ActivityLog:
        if (class_exists(ActivityLog::class)) {
            ActivityLog::create([
                'type'        => $type,
                'subject_id'  => is_object($subject) ? ($subject->id ?? null) : $subject,
                'subject_type'=> is_object($subject) ? get_class($subject) : null,
                'school_id'   => $schoolId,
                'before'      => $before ?: null,
                'after'       => $after ?: null,
                'message'     => $message,
                'created_by'  => auth()->id(),
            ]);
            return;
        }

        // Fallback: catat ke laravel.log supaya tidak putus alur
        Log::info('[activity] '.$type, [
            'subject'   => $subject,
            'before'    => $before,
            'after'     => $after,
            'school_id' => $schoolId,
            'message'   => $message,
            'user_id'   => auth()->id(),
        ]);
    }
if (!function_exists('rupiah')) {
    function rupiah($angka){ return 'Rp '.number_format((float)$angka, 0, ',', '.'); }
}//

}




