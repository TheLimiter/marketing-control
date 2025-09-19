<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function record(
        string $action,
        ?Model $subject = null,
        array $before = [],
        array $after  = [],
        ?int $schoolId = null,
        ?string $title = null
    ): void {
        $entityType = $subject ? get_class($subject) : null;
        $entityId   = $subject?->getKey();

        // Tebak schoolId bila null
        if ($schoolId === null && $subject) {
            if (isset($subject->master_sekolah_id)) {
                $schoolId = (int) $subject->master_sekolah_id;
            } elseif (class_basename($subject) === 'MasterSekolah') {
                $schoolId = (int) $subject->getKey();
            }
        }

        $req = request();
        ActivityLog::create([
            'user_id'           => auth()->id(),
            'master_sekolah_id' => $schoolId,
            'entity_type'       => $entityType,
            'entity_id'         => $entityId,
            'action'            => $action,
            'title'             => $title,
            'before'            => $before ?: null,
            'after'             => $after  ?: null,
            'ip'                => $req?->ip(),
            'user_agent'        => $req?->userAgent(),
        ]);
    }

    /** (opsional) alias agar backward-compat kalau sempat kepakai */
    public static function write(array $payload): void
    {
        self::record(
            $payload['action'] ?? 'unknown',
            $payload['subject'] ?? null,
            $payload['before'] ?? [],
            $payload['after'] ?? [],
            $payload['school_id'] ?? null,
            $payload['title'] ?? null,
        );
    }
}
