<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prospek extends Model
{
    use HasFactory;

    protected $table = 'prospek';

    protected $fillable = [
        'calon_klien_id',
        'tanggal',
        'jenis',
        'hasil',
        'catatan',
        'created_by',
        'mou_path',
        'mou_at',
        'ttd_status',
        'ttd_at',
        'ttd_by'
    ];

    /**
     * Pastikan ttd_status tidak di-cast sebagai boolean.
     * Ini mencegah masalah jika kolom di database adalah enum('sudah', 'belum').
     */
    protected $casts = [
        // 'ttd_status' => 'string', // Tidak perlu karena ini adalah default
    ];

    /**
     * Dapatkan calon klien yang terkait dengan prospek ini.
     */
    public function calon()
    {
        return $this->belongsTo(CalonKlien::class, 'calon_klien_id')->withDefault();
    }

    /**
     * Accessor untuk mengecek apakah prospek sudah memiliki MOU.
     * @return bool
     */
    public function getHasMouAttribute(): bool
    {
        // Anggap "punya MOU" bila mou_file ada; support mou_path sebagai fallback
        return !empty($this->mou_file ?? $this->mou_path ?? null);
    }

    /**
     * Accessor untuk mengecek apakah prospek sudah ditandatangani.
     * @return bool
     */
    public function getIsTtdAttribute(): bool
    {
        // True jika enum 'sudah' atau tinyint 1
        return ($this->ttd_status === 'sudah' || $this->ttd_status == 1);
    }
}
