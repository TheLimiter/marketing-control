<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    // biar fleksibel ke skema lama: cukup guard kosong
    protected $guarded = [];

    // cast JSON → array (walau di SQLite disimpan sebagai TEXT, ini tetap jalan)
    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
