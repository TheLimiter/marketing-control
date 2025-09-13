<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    protected $guarded = [];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function user()   { return $this->belongsTo(User::class); }

    // Polymorphic manual: pakai kolom lama entity_type/entity_id
    public function subject() { return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id'); }

    // Opsional: relasi ke sekolah bila Step A dilakukan
    public function sekolah() { return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id'); }

    // Fallback title: kalau kolom title null, generate dari action
    public function getTitleAttribute($val)
    {
        if ($val) return $val;
        $et = class_basename($this->entity_type ?? '');
        return trim(($this->action ?? 'Aktivitas') . ($et ? " ($et #{$this->entity_id})" : ''));
    }
}
