<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUser;
use App\Models\AktivitasProspek;

class PenggunaanModul extends Model
{
    use SoftDeletes, TracksUser, LogsActivity;

    /** Tabel */
    protected $table = 'penggunaan_modul';

    /** ====== STAGE (khusus progress modul) ====== */
    public const STAGE_DILATIH    = 'dilatih';
    public const STAGE_DIDAMPINGI = 'didampingi';
    public const STAGE_MANDIRI    = 'mandiri';

    public static function stageOptions(): array
    {
        return [
            self::STAGE_DILATIH    => 'Dilatih',
            self::STAGE_DIDAMPINGI => 'Didampingi',
            self::STAGE_MANDIRI    => 'Mandiri',
        ];
    }

    public function getStageLabelAttribute(): string
    {
        return self::stageOptions()[$this->stage_modul] ?? '—';
    }

    public function getStageBadgeClassAttribute(): string
    {
        return match ($this->stage_modul) {
            self::STAGE_DILATIH    => 'info',
            self::STAGE_DIDAMPINGI => 'warning',
            self::STAGE_MANDIRI    => 'success',
            default                => 'secondary',
        };
    }

    /** ====== STATUS bawaan progress modul ====== */
    public const ST_ATTACHED = 'attached';
    public const ST_PROGRESS = 'on_progress';
    public const ST_DONE     = 'done';
    public const ST_REOPEN   = 'reopen';
    public const ST_PAUSED   = 'paused';

    /** Mass-assignable */
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
        // catatan/notes (kompat)
        'catatan',
        'notes',
        'is_official',
        'harga',
        'diskon',
        'created_by',
        'updated_by',
        // NEW: stage penggunaan modul
        'stage_modul',
    ];

    /** Casting kolom */
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

    /** Accessors ikut diserialisasi (opsional) */
    protected $appends = [
        'is_done',
        'is_active',
        'computed_status',
        'lisensi_label',
        'stage_label',
        'stage_badge_class',
        // baru: convenience accessor untuk latest activity yang terbaik
        'latest_activity_best',
    ];

    /* =======================
     * RELASI
     * ======================= */
    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(MasterSekolah::class, 'master_sekolah_id');
    }

    /** Back-compat: $pm->klien */
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

    /**
     * Latest activity for this module + school.
     * Returns AktivitasProspek row where modul_id matches this modul_id AND master_sekolah_id matches.
     */
    public function latestActivityModule(): HasOne
{
    return $this->hasOne(AktivitasProspek::class, 'master_sekolah_id', 'master_sekolah_id')
                ->where('modul_id', $this->modul_id)
                ->latestOfMany(); // Gunakan latestOfMany() tanpa argumen
}

    /**
     * Latest activity for this school (ignore modul_id).
     * Useful as fallback for showing last note even if no modul_id stored in AktivitasProspek.
     */
    public function latestActivitySchool(): HasOne
    {
        return $this->hasOne(AktivitasProspek::class, 'master_sekolah_id', 'master_sekolah_id')
                    ->latestOfMany('tanggal');
    }

    /**
     * Accessor: choose the best latest activity to show:
     * prefer modul-specific, fallback to school-level.
     *
     * This accessor will use eager-loaded relations if available (avoid extra queries).
     */
    public function getLatestActivityBestAttribute()
    {
        // prefer eager-loaded relation if present
        if ($this->relationLoaded('latestActivityModule')) {
            $mod = $this->latestActivityModule;
            if ($mod) return $mod;
        } else {
            // lazy-load module-specific first
            $mod = $this->latestActivityModule()->first();
            if ($mod) return $mod;
        }

        if ($this->relationLoaded('latestActivitySchool')) {
            return $this->latestActivitySchool;
        }

        return $this->latestActivitySchool()->first();
    }

    /* =======================
     * HELPERS STATUS
     * ======================= */
    public function isDone(): bool
    {
        $st = strtolower((string) $this->status);
        return in_array($st, [self::ST_DONE, 'ended', 'selesai', 'complete', 'completed'], true)
            || !is_null($this->finished_at);
    }

    public function isActive(): bool
    {
        $st = strtolower((string) $this->status);
        if ($this->isDone()) return false;
        if ($st === self::ST_PAUSED) return false;

        return in_array($st, [
            self::ST_PROGRESS, 'active', 'aktif', 'on_progress', 'reopen', 'attached', null, ''
        ], true);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::ST_PROGRESS
            || (!is_null($this->started_at) && is_null($this->finished_at));
    }

    /* =======================
     * ACCESSORS / MUTATORS catatan/notes (kompat)
     * ======================= */
    public function getCatatanAttribute(): ?string
    {
        if (array_key_exists('catatan', $this->attributes)) {
            return $this->attributes['catatan'];
        }
        return $this->attributes['notes'] ?? null;
    }

    public function setCatatanAttribute($value): void
    {
        if (array_key_exists('catatan', $this->attributes)) {
            $this->attributes['catatan'] = $value;
        }
        $this->attributes['notes'] = $value;
    }

    /* =======================
     * ACCESSORS ringkasan lain
     * ======================= */
    public function getLisensiLabelAttribute(): string
    {
        return $this->is_official ? 'Official' : 'Trial';
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->isDone()) return self::ST_DONE;
        if ($this->isActive()) return $this->status ?? self::ST_ATTACHED;
        return $this->status ?? self::ST_ATTACHED;
    }

    // alias properties untuk boolean
    public function getIsDoneAttribute(): bool  { return $this->isDone(); }
    public function getIsActiveAttribute(): bool { return $this->isActive(); }

    /* =======================
     * SCOPES
     * ======================= */
    public function scopeDone($q)
    {
        return $q->where('status', self::ST_DONE)->orWhereNotNull('finished_at');
    }

    public function scopeInProgress($q)
    {
        return $q->where('status', self::ST_PROGRESS)
                 ->orWhere(function($qq){
                     $qq->whereNotNull('started_at')->whereNull('finished_at');
                 });
    }

    /** Filter berdasarkan stage_modul (dilatih/didampingi/mandiri) */
    public function scopeStage($q, ?string $stage)
    {
        if ($stage === null || $stage === '') return $q;
        return $q->where('stage_modul', $stage);
    }

    /* =======================
     * UTIL: deteksi overlap jadwal
     * ======================= */
    public function overlaps(int $masterId, int $modulId, ?string $start, ?string $end, ?int $ignoreId = null): bool
    {
        return self::where('master_sekolah_id', $masterId)
            ->where('modul_id', $modulId)
            ->when($ignoreId, fn($q)=>$q->where('id','!=',$ignoreId))
            ->where(function($q) use ($start, $end) {
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

    public function activitiesForModule(): HasMany
{
    // Cek apakah kolom 'modul_id' ada di tabel 'aktivitas_prospek'
    // untuk menghindari error jika migrasi belum dijalankan.
    if (! Schema::hasColumn('aktivitas_prospek', 'modul_id')) {
        // Kembalikan relasi kosong jika kolom tidak ada
        return $this->hasMany(AktivitasProspek::class, 'master_sekolah_id', 'id')->whereRaw('1=0');
    }

    return $this->hasMany(AktivitasProspek::class, 'master_sekolah_id', 'master_sekolah_id')
                ->where('aktivitas_prospek.modul_id', $this->modul_id);
}
}
