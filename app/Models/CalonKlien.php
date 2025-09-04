<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CalonKlien extends Model
{
    use HasFactory;

    protected $table = 'calon_klien';

    protected $fillable = [
        'nama',
        'alamat',
        'no_hp',
        'narahubung',
        'jenjang',
        'sumber',
        'catatan',
        'created_by',
        'tanggal_mou',
        'status_ttd',
        'mou_file',
    ];
    protected $casts = [
        'tanggal_mou' => 'date',
    ];

    public function prospek(){ return $this->hasMany(AktivitasProspek::class,'calon_klien_id'); }
    public function creator(){ return $this->belongsTo(User::class,'created_by'); }
}

