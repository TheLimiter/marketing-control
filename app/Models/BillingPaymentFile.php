<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPaymentFile extends Model
{
    protected $fillable = [
        'tagihan_id',
        'aktivitas_id',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
    ];

    public function tagihan()
    {
        return $this->belongsTo(TagihanKlien::class, 'tagihan_id');
    }

    public function aktivitas()
    {
        return $this->belongsTo(AktivitasProspek::class, 'aktivitas_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
