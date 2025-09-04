<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUser;
use Carbon\Carbon;

class TagihanKlien extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TracksUser;
    use LogsActivity;

    protected $table = 'tagihan_klien';

    protected $fillable = [
        'master_sekolah_id',
        'penggunaan_modul_id',
        'nomor',
        'jatuh_tempo',
        'total',
        'terbayar',
        'status',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_tagihan' => 'date',
        'jatuh_tempo'     => 'date',
        'total'           => 'integer',
        'terbayar'        => 'integer',
    ];

    protected $appends = ['sisa'];

    public function getSisaAttribute(): int
    {
        return max(0, (int)($this->total ?? 0) - (int)($this->terbayar ?? 0));
    }

    protected static function booted(): void
    {
        static::creating(function ($m) {
            if (empty($m->tanggal_tagihan)) $m->tanggal_tagihan = now()->toDateString();
            if (is_null($m->terbayar))      $m->terbayar = 0;
            if (empty($m->status))          $m->status = 'draft';
        });

        static::saving(function (self $m) {
            $m->total    = (int) ($m->total ?? 0);
            $m->terbayar = max(0, (int) ($m->terbayar ?? 0));

            // clamp
            if ($m->terbayar > $m->total) $m->terbayar = $m->total;

            // Paid/Open
            if ($m->terbayar >= $m->total && $m->total > 0) {
                $m->status = 'paid';
            } elseif ($m->status === 'paid' && $m->terbayar < $m->total) {
                $m->status = 'open';
            } elseif (empty($m->status)) {
                $m->status = $m->total > 0 ? 'open' : 'draft';
            }

            // Overdue auto (hanya jika belum paid)
            if ($m->status !== 'paid' && $m->jatuh_tempo && $m->jatuh_tempo->isPast() && $m->sisa > 0) {
                $m->status = 'overdue';
            }
        });
    }

    public function sekolah()
    {
        return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id');
    }

    public function penggunaan()
    {
        return $this->belongsTo(PenggunaanModul::class, 'penggunaan_modul_id');
    }

    // Alias untuk relasi sekolah
    public function klien()
    {
        return $this->belongsTo(\App\Models\MasterSekolah::class, 'master_sekolah_id');
    }

    public function notifikasi()
    {
        return $this->hasMany(\App\Models\Notifikasi::class, 'tagihan_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->jatuh_tempo) return false;
        return Carbon::parse($this->jatuh_tempo)->isPast() && $this->sisa > 0;
    }

    public function getDueBadgeAttribute(): ?string
    {
        if (!$this->jatuh_tempo) return null;
        $dt = Carbon::parse($this->jatuh_tempo);
        if ($dt->isPast()) return 'danger';
        return $dt->diffInDays(now()) <= 3 ? 'warning' : 'secondary';
    }

    public function getWaUrlAttribute(): ?string
    {
        $hp = $this->sekolah->no_hp ?? null;
        if (!$hp) return null;
        $plain = preg_replace('/\D+/', '', $hp);
        if (str_starts_with($plain, '0')) { $plain = '62'.substr($plain, 1); }

        $text = rawurlencode(
            "Halo {$this->sekolah->nama_sekolah}, pengingat tagihan {$this->nomor} ".
            "sebesar Rp".number_format($this->sisa,0,',','.').
            " jatuh tempo {$this->jatuh_tempo}. Terima kasih."
        );

        return "https://wa.me/{$plain}?text={$text}";
    }

    public function scopeFilter($q, array $f)
    {
        $q->when($f['master_sekolah_id'] ?? null, fn($qq,$v)=>$qq->where('master_sekolah_id',$v));
        $q->when($f['status'] ?? null, fn($qq,$v)=>$qq->where('status',$v));
        $q->when(($f['dari'] ?? null) && ($f['sampai'] ?? null), function($qq) use ($f){
            $qq->whereBetween('jatuh_tempo', [$f['dari'],$f['sampai']]);
        });
        $q->when($f['q'] ?? null, fn($qq,$v)=>$qq->where('nomor','like','%'.$v.'%'));
    }
}
