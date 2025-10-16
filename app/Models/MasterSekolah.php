<?php

namespace App\Models;

use App\Models\Mou;
use App\Models\AktivitasProspek;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterSekolah extends Model
{
    use LogsActivity, TracksUser, SoftDeletes;

    protected $table = 'master_sekolah';

    protected $fillable = [
        'nama_sekolah','alamat','no_hp','sumber','catatan',
        'jenjang','narahubung','status_klien','tindak_lanjut','jumlah_siswa',
        'mou_path','ttd_status','mou_catatan',
        'stage','stage_changed_at','created_by','updated_by',
    ];

    /**
     * Mapping stage BARU:
     * 1 = Calon
     * 2 = sudah dihubungi
     * 3 = sudah dilatih
     * 4 = MOU Aktif
     * 5 = Tindak lanjut MOU
     * 6 = Ditolak
     *
     * Pastikan nilai integer sesuai data yang ada di DB.
     */
    public const ST_CALON  = 1;
    public const ST_SHB    = 2; // sudah dihubungi
    public const ST_SLTH   = 3; // sudah dilatih
    public const ST_MOU    = 4; // MOU Aktif
    public const ST_TLMOU  = 5; // Tindak lanjut MOU
    public const ST_TOLAK  = 6; // Ditolak

    protected $casts = [
        'stage'            => 'integer',
        'stage_changed_at' => 'datetime',
        'ttd_status'       => 'boolean',
    ];

    // ---------- Accessors ----------
    public function getMouOkAttribute(): bool
    {
        return (bool) ($this->mou_path ?? false);
    }

    public function getTtdOkAttribute(): bool
    {
        return (bool) ($this->ttd_status ?? false);
    }

    public function getNamaDisplayAttribute(): ?string
    {
        return $this->nama_sekolah ?? $this->nama ?? null;
    }

    public function getNamaAttribute()
    {
        return $this->nama_sekolah ?? null;
    }

    // Persentase progres modul (0-100)
    public function getModulPercentAttribute(): int
    {
        $total = (int) ($this->total_modul ?? 0);
        $done  = (int) ($this->modul_done  ?? 0);
        return $total > 0 ? (int) round(100 * $done / $total) : 0;
    }

    // ---------- Stage helpers ----------
    public const STAGE_LABELS = [
        self::ST_CALON => 'Calon',
        self::ST_SHB   => 'sudah dihubungi',
        self::ST_SLTH  => 'sudah dilatih',
        self::ST_MOU   => 'MOU Aktif',
        self::ST_TLMOU => 'Tindak lanjut MOU',
        self::ST_TOLAK => 'Ditolak',
    ];

    // Label stage: terima null agar tidak TypeError
    public static function stageLabel(?int $s): string
    {
        $s ??= self::ST_CALON;
        return self::STAGE_LABELS[$s] ?? (string) $s;
    }

    // Opsi lengkap (berguna untuk dropdown)
    public static function stageOptions(): array
    {
        return self::STAGE_LABELS;
    }

    // ---------- Scopes ----------
    public function scopeCalon(Builder $q): Builder
    {
        return $q->where('status_klien', 'calon');
    }

    public function scopeProspek(Builder $q): Builder
    {
        return $q->where('status_klien', 'prospek');
    }

    public function scopeKlien(Builder $q): Builder
    {
        return $q->where('status_klien', 'klien');
    }

    public function scopeStage(Builder $q, ?int $s): Builder
    {
        if ($s === null) return $q;
        return $q->where('stage', $s);
    }

    public function scopeHasMou(Builder $q, bool $yes = true): Builder
    {
        return $yes ? $q->whereNotNull('mou_path') : $q->whereNull('mou_path');
    }

    public function scopeHasTtd(Builder $q, bool $yes = true): Builder
    {
        return $yes
            ? $q->where('ttd_status', 1)
            : $q->where(fn ($x) => $x->whereNull('ttd_status')->orWhere('ttd_status', 0));
    }

    // ---------- Stage Ops ----------
    public function moveToStage(int $to, ?string $note = null): void
    {
        $from = (int) ($this->getOriginal('stage') ?? self::ST_CALON);

        // (Opsional) Wajib MOU sebelum Tindak lanjut MOU jika require_mou_ttd=true
        $require = (bool) config('biz.require_mou_ttd', false);
        if ($require && $to === self::ST_TLMOU && (int) ($this->stage ?? 0) < self::ST_MOU) {
            throw new \DomainException('Set MOU terlebih dahulu sebelum Tindak lanjut MOU.');
        }

        $this->forceFill([
            'stage'             => $to,
            'stage_changed_at'  => now(),
        ])->save();

        // Catat aktivitas
        $this->aktivitas()->create([
            'tanggal'    => now(),
            'jenis'      => 'stage_change',
            'hasil'      => $from . '→' . $to,
            'catatan'    => $note,
            'created_by' => auth()->id(),
        ]);
    }

    protected static function booted(): void
    {
        // Default-kan stage saat create jika belum diisi
        static::creating(function (self $m) {
            if ($m->stage === null) {
                $m->stage = self::ST_CALON;
            }
        });
    }

    protected function getEntityType(): string
    {
        return match ($this->stage) {
            self::ST_CALON  => 'calon',
            self::ST_SHB    => 'sudah_dihubungi',
            self::ST_SLTH   => 'sudah_dilatih',
            self::ST_MOU    => 'mou_aktif',
            self::ST_TLMOU  => 'tindak_lanjut_mou',
            self::ST_TOLAK  => 'ditolak',
            default         => 'sekolah',
        };
    }

    // ---------- Relasi ----------
    public function mouRows()
    {
        return $this->hasMany(Mou::class, 'master_sekolah_id');
    }

    public function aktivitas()
    {
        return $this->hasMany(AktivitasProspek::class, 'master_sekolah_id');
    }

    public function penggunaanModul()
    {
        return $this->hasMany(\App\Models\PenggunaanModul::class, 'master_sekolah_id');
    }

    public function modulUsages()
    {
        return $this->hasMany(\App\Models\PenggunaanModul::class, 'master_sekolah_id');
    }

    public function progressItems()
    {
        return $this->hasMany(\App\Models\ProgressModul::class, 'master_sekolah_id');
    }
}
