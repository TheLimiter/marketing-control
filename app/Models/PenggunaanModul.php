<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUser;

class PenggunaanModul extends Model
{
    use SoftDeletes, TracksUser, LogsActivity;

    protected $table = 'penggunaan_modul';

    public const ST_ATTACHED = 'attached';
    public const ST_PROGRESS = 'on_progress';
    public const ST_DONE     = 'done';
    public const ST_REOPEN   = 'reopen';
    public const ST_PAUSED   = 'paused'; // Menambahkan konstanta PAUSED untuk konsistensi

    protected $fillable = [
        'master_sekolah_id',
        'modul_id',
        'pengguna_nama',
        'pengguna_kontak',
        'status',
        'started_at',
        'finished_at',
        'reopened_at',
        'mulai_tanggal',
        'akhir_tanggal',
        'last_used_at',
        'notes',
        'is_official',
        'harga',
        'diskon',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'finished_at'   => 'datetime',
        'reopened_at'   => 'datetime',
        'mulai_tanggal' => 'date',
        'akhir_tanggal' => 'date',
        'last_used_at'  => 'datetime',
        'is_official'   => 'boolean',
        'harga'         => 'integer',
        'diskon'        => 'integer',
    ];

    // === RELASI ===
    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id');
    }

    public function sekolah(): BelongsTo
    {
        // alias agar with(['sekolah']) & whereHas('sekolah') jalan
        return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id');
    }

    // Alias balik-compat kalau view lama masih memanggil $pm->klien
    public function klien(): BelongsTo
    {
        return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id');
    }

    public function modul(): BelongsTo
    {
        return $this->belongsTo(Modul::class, 'modul_id');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(TagihanKlien::class, 'penggunaan_modul_id');
    }

    // === HELPERS STATUS ===

    /** Selesai jika explicit 'done/ended/selesai' ATAU punya finished_at */
    public function isDone(): bool
    {
        $st = strtolower((string) $this->status);
        return in_array($st, [self::ST_DONE, 'ended', 'selesai', 'complete', 'completed'], true)
            || !is_null($this->finished_at);
    }

    /** Aktif/berjalan bila belum selesai dan bukan 'paused' */
    public function isActive(): bool
    {
        $st = strtolower((string) $this->status);
        // Pastikan bukan "selesai" dan bukan "paused"
        if ($this->isDone()) return false;
        if ($st === self::ST_PAUSED) return false;

        // Dianggap aktif jika statusnya:
        return in_array($st, [
            self::ST_PROGRESS, 'active', 'aktif', 'on_progress', 'reopen', 'attached', null, ''
        ], true);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::ST_PROGRESS
            || (!is_null($this->started_at) && is_null($this->finished_at));
    }

    // === ACCESSORS ===

    public function getLisensiLabelAttribute(): string
    {
        return $this->is_official ? 'Official' : 'Trial';
    }

    public function getComputedStatusAttribute(): string
    {
        // Gunakan helper yang sudah ada
        if ($this->isDone()) return self::ST_DONE;
        if ($this->isActive()) return $this->status ?? self::ST_ATTACHED; // Mengembalikan status asli, atau 'attached' jika null
        return $this->status ?? self::ST_ATTACHED;
    }

    // accessor biar bisa dipakai sebagai properti: $pm->is_done
    public function getIsDoneAttribute(): bool
    {
        return $this->isDone();
    }

    // accessor biar bisa dipakai sebagai properti: $pm->is_active
    public function getIsActiveAttribute(): bool
    {
        return $this->isActive();
    }

    // === Query Scopes ===
    public function scopeDone($q)
    {
        return $q->where('status', self::ST_DONE)->orWhereNotNull('finished_at');
    }

    public function scopeInProgress($q)
    {
        return $q->where('status', self::ST_PROGRESS)
             ->orWhere(function($qq){ $qq->whereNotNull('started_at')->whereNull('finished_at'); });
    }

    // === HELPER LAINNYA ===

    // helper overlap yang dipanggil di controller
    public function overlaps(int $masterId, int $modulId, ?string $start, ?string $end, ?int $ignoreId = null): bool
    {
        return self::where('master_sekolah_id', $masterId)
            ->where('modul_id', $modulId)
            ->when($ignoreId, fn($q)=>$q->where('id','!=',$ignoreId))
            ->where(function($q) use ($start, $end) {
                // jika tidak ada akhir_tanggal = dianggap ongoing
                if ($end === null) {
                    $q->whereNull('akhir_tanggal')
                      ->orWhere('akhir_tanggal', '>=', $start);
                } else {
                    $q->where(function($q) use ($start, $end){
                        $q->whereBetween('mulai_tanggal', [$start, $end])
                          ->orWhereBetween('akhir_tanggal', [$start, $end])
                          ->orWhere(function($q) use ($start, $end){
                              $q->where('mulai_tanggal','<=',$start)
                                ->where(function($q) use ($end){
                                    $q->whereNull('akhir_tanggal')
                                      ->orWhere('akhir_tanggal','>=',$end);
                                });
                          });
                    });
                }
            })->exists();
    }
}
