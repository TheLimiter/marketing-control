<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUser;

class Modul extends Model
{
    use LogsActivity;

    protected $table = 'modul';

    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'versi',
        'deskripsi',
        'harga_default',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'harga_default' => 'integer',
    ];

    public function scopeAktif($q){ return $q->where('aktif', 1); }

    // === RELASI ===
    public function tagihan()
    {
        return $this->belongsToMany(TagihanKlien::class, 'tagihan_modul', 'modul_id', 'tagihan_id')
                    ->withPivot(['keterangan'])
                    ->withTimestamps();
    }
}
