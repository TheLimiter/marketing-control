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

    protected $appends = ['badge_class', 'display_jenis', 'display_hasil'];

    public function master()  { return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function files()   { return $this->hasMany(AktivitasFile::class, 'aktivitas_id'); }

    public function getCreatorNameAttribute(): string
    {
        return $this->creator?->name ?? '';
    }

    // LABEL JENIS
    public function getJenisLabelAttribute(): string
    {
        $map = [
            'stage_change'     => 'Perubahan Tahap',
            'modul_attach'     => 'Lampiran Modul',
            'modul_progress'   => 'Progres Modul',
            'modul_done'       => 'Modul Selesai',
            'modul_reopen'     => 'Modul Dibuka Ulang',
            'mou_update'       => 'MOU / TTD',
            'kunjungan'        => 'Kunjungan',
            'meeting'          => 'Meeting',
            'follow_up'        => 'Follow Up',
            'whatsapp'         => 'WhatsApp',
            'email'            => 'Email',
            'lainnya'          => 'Lainnya',
            // kalau masih ada event lama:
            'prospek.to_klien' => 'Konversi ke Klien',
        ];

        if (isset($map[$this->jenis])) {
            return $map[$this->jenis]; // <- perbaikan dari $thisu
        }

        return Str::headline(str_replace('.', ' ', (string) $this->jenis));
    }

    // HASIL
    public function getHasilLabelAttribute(): ?string
    {
        $hasil = $this->hasil;

        if ($this->jenis === 'stage_change' && $hasil) {
            if (preg_match('/(\d+)\s*(?:|->|to)\s*(\d+)/i', $hasil, $m)) {
                $from = (int) $m[1];
                $to   = (int) $m[2];
                return MasterSekolah::stageLabel($from) . '' . MasterSekolah::stageLabel($to);
            }
        }

        if ($this->jenis === 'module_status' && $hasil) {
            [$lisensi, $status] = array_map('trim', explode('/', $hasil) + [null, null]);
            $lisensiLbl = (strtolower($lisensi) === 'official') ? 'Official' : 'Trial';
            $statusLbl  = $status ? ucfirst(strtolower($status)) : '';
            return trim($lisensiLbl . ($statusLbl ? ' / ' . $statusLbl : ''));
        }

        return $hasil;
    }

    // --- KELAS BADGE untuk feed/dashboard ---
    public function getBadgeClassAttribute(): string
    {
        $k = strtolower($this->jenis ?? 'lainnya');

        $map = [
            'modul_progress' => 'bg-info text-white',
            'modul_done'     => 'bg-success text-white',
            'modul_reopen'   => 'bg-warning text-dark',
            'modul_attach'   => 'bg-secondary text-white',
            'stage_change'   => 'bg-dark text-white',
            'kunjungan'      => 'bg-primary text-white',
            'meeting'        => 'bg-secondary text-white',
            'follow_up'      => 'bg-secondary text-white',
            'whatsapp'       => 'bg-success text-white',
            'email'          => 'bg-secondary text-white',
            'lainnya'        => 'bg-light text-dark',
        ];

        return 'rounded-pill '.($map[$k] ?? 'bg-secondary text-white');
    }

    // --- alias yang dipakai di Blade ---
    public function getDisplayJenisAttribute(): string
    {
        // khusus stage_change: tampilkan arah kalau bisa dibaca dari hasil
        if ($this->jenis === 'stage_change' && ($h = $this->getHasilLabelAttribute())) {
            return $h; // Prospek Negosiasi, dsb.
        }
        return $this->getJenisLabelAttribute();
    }

    public function getDisplayHasilAttribute(): ?string
    {
        return $this->getHasilLabelAttribute();
    }

    public function paymentFiles()
    {
        return $this->hasMany(\App\Models\BillingPaymentFile::class, 'aktivitas_id');
    }
}
