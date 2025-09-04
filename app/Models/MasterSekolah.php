<?php

namespace App\Models;


use App\Models\Mou;
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

    public const ST_CALON=1, ST_PROSPEK=2, ST_NEGOSIASI=3, ST_MOU=4, ST_KLIEN=5;

    protected $casts = [
        'stage' => 'integer',
        'stage_changed_at' => 'datetime',
        'ttd_status' => 'boolean',
    ];

    // Accessors
    public function getMouOkAttribute(): bool { return (bool) ($this->mou_path ?? false); }
    public function getTtdOkAttribute(): bool { return (bool) ($this->ttd_status ?? false); }
    public function getNamaDisplayAttribute(): ?string { return $this->nama_sekolah ?? $this->nama ?? null; }
    public function getNamaAttribute() { return $this->nama_sekolah ?? null; }

    // Accesor untuk persen modul
    public function getModulPercentAttribute(): int
    {
        $total = (int) ($this->total_modul ?? 0);
        $done  = (int) ($this->modul_done  ?? 0);
        return $total > 0 ? (int) round(100 * $done / $total) : 0;
    }

    public static function stageLabel(int $s): string
    {
        return match($s){
            self::ST_CALON=>'Calon', self::ST_PROSPEK=>'Prospek',
            self::ST_NEGOSIASI=>'Negosiasi', self::ST_MOU=>'MOU',
            self::ST_KLIEN=>'Klien', default=>(string)$s,
        };
    }

    // Scopes
    public function scopeCalon(Builder $q): Builder { return $q->where('status_klien','calon'); }
    public function scopeProspek(Builder $q): Builder { return $q->where('status_klien','prospek'); }
    public function scopeKlien(Builder $q): Builder { return $q->where('status_klien','klien'); }
    public function scopeStage($q,int $s){ return $q->where('stage',$s); }
    public function scopeHasMou($q,bool $yes=true){ return $yes? $q->whereNotNull('mou_path') : $q->whereNull('mou_path'); }
    public function scopeHasTtd($q,bool $yes=true){
        return $yes ? $q->where('ttd_status',1)
                     : $q->where(fn($x)=>$x->whereNull('ttd_status')->orWhere('ttd_status',0));
    }

    // Stage
    public function moveToStage(int $to, ?string $note = null): void
    {
        // ambil stage sebelum berubah
        $from = (int) ($this->getOriginal('stage') ?? self::ST_CALON);

        // === ubah: pakai config untuk enforce MOU sebelum Klien
        $require = (bool) config('biz.require_mou_ttd', false);
        if ($require && $to === self::ST_KLIEN && (int) ($this->stage ?? 0) < self::ST_MOU) {
            throw new \DomainException('Set MOU terlebih dahulu sebelum jadi Klien.');
        }

        $this->forceFill([
            'stage'             => $to,
            'stage_changed_at' => now(),
        ])->save();

        // catat aktivitas (pakai konkatenasi agar aman)
        $this->aktivitas()->create([
            'tanggal'      => now(),
            'jenis'      => 'stage_change',
            'hasil'      => $from . '→' . $to,
            'catatan'      => $note,
            'created_by' => auth()->id(),
        ]);
    }

    protected function getEntityType(): string
    {
        return match ($this->stage) {
            self::ST_CALON=>'calon', self::ST_PROSPEK=>'prospek',
            self::ST_NEGOSIASI=>'negosiasi', self::ST_MOU=>'mou',
            self::ST_KLIEN=>'klien', default=>'sekolah',
        };
    }

    // Relasi
    public function mouRows(){ return $this->hasMany(Mou::class,'master_sekolah_id'); }
    public function aktivitas(){ return $this->hasMany(AktivitasProspek::class,'master_sekolah_id'); }
    public function penggunaanModul(){ return $this->hasMany(\App\Models\PenggunaanModul::class, 'master_sekolah_id');}

    public function modulUsages() // assignment modul ke sekolah
    {
        return $this->hasMany(\App\Models\PenggunaanModul::class, 'master_sekolah_id');
    }

    public function progressItems() // progress modul per sekolah
    {
        return $this->hasMany(\App\Models\ProgressModul::class, 'master_sekolah_id');
    }
}
