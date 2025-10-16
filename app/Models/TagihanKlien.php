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
    use HasFactory, SoftDeletes, TracksUser, LogsActivity;

    protected $table = 'tagihan_klien';

    protected $fillable = [
        'master_sekolah_id',
        'penggunaan_modul_id',   // biarkan untuk kompatibilitas lama (boleh tak dipakai)
        'nomor',
        'tanggal_tagihan',
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

    // ======== RELATIONS ========
    public function sekolah() { return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id'); }
    public function penggunaan() { return $this->belongsTo(PenggunaanModul::class, 'penggunaan_modul_id'); }
    public function klien() { return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id'); }
    public function notifikasi() { return $this->hasMany(Notifikasi::class, 'tagihan_id'); }
    public function paymentFiles() { return $this->hasMany(BillingPaymentFile::class, 'tagihan_id'); }

    // NEW: gelondongan modul (pivot)
    public function modul()
    {
        return $this->belongsToMany(Modul::class, 'tagihan_modul', 'tagihan_id', 'modul_id')
                    ->withPivot(['keterangan'])
                    ->withTimestamps();
    }

    // ======== ACCESSORS ========
    public function getSisaAttribute(): int
    {
        return max(0, (int)($this->total ?? 0) - (int)($this->terbayar ?? 0));
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
        if (str_starts_with($plain, '0')) $plain = '62'.substr($plain, 1);

        $text = rawurlencode(
            "Halo {$this->sekolah->nama_sekolah}, pengingat tagihan {$this->nomor} ".
            "sebesar Rp".number_format($this->sisa,0,',','.').
            " jatuh tempo {$this->jatuh_tempo}. Terima kasih."
        );
        return "https://wa.me/{$plain}?text={$text}";
    }

    // ======== SCOPES ========
    public function scopeFilter($q, array $f)
    {
        $q->when($f['master_sekolah_id'] ?? null, fn($qq,$v)=>$qq->where('master_sekolah_id',$v));
        $q->when($f['status'] ?? null, fn($qq,$v)=>$qq->where('status',$v));
        $q->when(($f['dari'] ?? null) && ($f['sampai'] ?? null),
            fn($qq)=>$qq->whereBetween('jatuh_tempo', [$f['dari'],$f['sampai']]));
        $q->when($f['q'] ?? null, fn($qq,$v)=>$qq->where('nomor','like','%'.$v.'%'));
    }

    // ======== HOOKS ========
    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->tanggal_tagihan)) $m->tanggal_tagihan = now()->toDateString();
            if (is_null($m->terbayar))      $m->terbayar = 0;
            if (empty($m->status))          $m->status = 'draft';
        });

        static::saving(function (self $m) {
            $m->total    = (int) ($m->total ?? 0);
            $m->terbayar = max(0, (int) ($m->terbayar ?? 0));
            if ($m->terbayar > $m->total) $m->terbayar = $m->total;

            // status sederhana
            if ($m->terbayar >= $m->total && $m->total > 0)      $m->status = 'lunas';
            elseif ($m->terbayar > 0 && $m->terbayar < $m->total) $m->status = 'sebagian';
            elseif (empty($m->status))                             $m->status = 'draft';

            // overdue
            if ($m->status !== 'lunas' && $m->jatuh_tempo && $m->jatuh_tempo->isPast() && $m->sisa > 0) {
                $m->status = 'sebagian'; // tetap sebagian; penandaan overdue pakai badge
            }
        });
    }
}
