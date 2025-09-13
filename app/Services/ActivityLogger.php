<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ActivityLogger
{
    /**
     * Catat aktivitas ke tabel lama (entity_type/entity_id, action, before/after).
     * $subject: model terkait (TagihanKlien, Mou, PenggunaanModul, MasterSekolah, dll)
     * $schoolId: isi bila mau langsung scope ke sekolah (lebih cepat untuk feed per-sekolah)
     *            Bila null, kita coba tebak dari $subject->master_sekolah_id atau $subject->id (kalau MasterSekolah).
     */
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

        // tebak schoolId kalau belum diberikan
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
            'entity_type'       => $entityType ? $entityType : ($before['entity_type'] ?? null),
            'entity_id'         => $entityId   ? $entityId   : ($before['entity_id'] ?? 0),
            'action'            => $action,
            'title'             => $title,
            'before'            => $before ?: null,
            'after'             => $after  ?: null,
            'ip'                => $req?->ip(),
            'user_agent'        => $req?->userAgent(),
        ]);
    }
}
