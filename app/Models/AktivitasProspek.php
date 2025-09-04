<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AktivitasProspek extends Model
{
    use SoftDeletes;

    protected $table = 'aktivitas_prospek';

    protected $fillable = [
        'master_sekolah_id','prospek_id','tanggal','jenis','hasil','catatan','created_by'
    ];

    protected $casts = ['tanggal' => 'datetime'];

    public function master()
    {
        return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id');
    }

    // ⬇️ tambahkan ini
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(AktivitasFile::class, 'aktivitas_id');
    }

    public function getCreatorNameAttribute(): string
    {
        return $this->creator?->name ?? '—';
    }

    public function getJenisLabelAttribute(): string
    {
        $map = [
            'stage_change'      => 'Perubahan Tahap',
            'module_assign'     => 'Penugasan Modul',
            'module_status'     => 'Status Modul',
            'module_use'        => 'Catat Penggunaan',
            'mou_upload'        => 'Unggah MOU',
            'ttd_mark'          => 'TTD Ditandai',
            'prospek.to_klien'  => 'Konversi ke Klien',
        ];
        if (isset($map[$this->jenis])) return $map[$thisu->jenis];
        return Str::headline(str_replace('.', ' ', (string)$this->jenis));
    }

    public function getHasilLabelAttribute(): ?string
    {
        $hasil = $this->hasil;

        // Perubahan tahap: dukung "5->1" / "5→1" / "5 to 1"
        if ($this->jenis === 'stage_change' && $hasil) {
            if (preg_match('/(\d+)\s*(?:→|->|to)\s*(\d+)/i', $hasil, $m)) {
                $from = (int) $m[1];
                $to   = (int) $m[2];
                return MasterSekolah::stageLabel($from) . ' → ' . MasterSekolah::stageLabel($to);
            }
        }


        // Status modul: "official / paused" → "Official / Paused"
        if ($this->jenis === 'module_status' && $hasil) {
            [$lisensi, $status] = array_map('trim', explode('/', $hasil) + [null, null]);
            $lisensiLbl = (strtolower($lisensi) === 'official') ? 'Official' : 'Trial';
            $statusLbl  = $status ? ucfirst(strtolower($status)) : '';
            return trim($lisensiLbl . ($statusLbl ? ' / ' . $statusLbl : ''));
        }

        return $hasil;
    }
}
