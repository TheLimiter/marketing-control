<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';
    protected $fillable = [
    'tagihan_id',
    'saluran',       // Email, WA, SMS
    'isi_pesan',     // <- ganti dari 'pesan' ke 'isi_pesan'
    'status',        // Antri, Terkirim, Gagal
    'created_by',
    'sent_at',
];
    public function tagihan()
    {
        return $this->belongsTo(\App\Models\TagihanKlien::class, 'tagihan_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
