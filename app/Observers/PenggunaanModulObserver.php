<?php

namespace App\Observers;

use App\Models\PenggunaanModul;
use Illuminate\Support\Arr;

class PenggunaanModulObserver
{
    public function created(PenggunaanModul $m): void
    {
        $schoolId = $m->master_sekolah_id ?? $m->klien_id ?? null;
        log_activity('modul.use_add', $m, [], [
            'modul_id' => $m->modul_id,
            'id'       => $m->id,
        ], $schoolId, 'Modul ditambahkan');
    }

    public function updated(PenggunaanModul $m): void
    {
        $schoolId = $m->master_sekolah_id ?? $m->klien_id ?? null;
        $dirty    = array_keys($m->getDirty());

        // abaikan update rutin
        $ignore = ['last_used_at','updated_at'];
        $effectiveDirty = array_values(array_diff($dirty, $ignore));

        if (empty($effectiveDirty)) {
            return;
        }

        $watched = [
            'status','is_official','mulai_tanggal','akhir_tanggal',
            'pengguna_nama','pengguna_kontak','catatan','modul_id'
        ];
        $changed = array_values(array_intersect($effectiveDirty, $watched));

        if (!empty($changed)) {
            log_activity('modul.use_update', $m,
                Arr::only($m->getOriginal(), $changed),
                Arr::only($m->getAttributes(), $changed),
                $schoolId,
                'Modul penggunaan diperbarui'
            );
        }
    }

    public function deleted(PenggunaanModul $m): void
    {
        $schoolId = $m->master_sekolah_id ?? $m->klien_id ?? null;
        log_activity('modul.use_remove', $m, [], [
            'modul_id' => $m->modul_id,
            'id'       => $m->id,
        ], $schoolId, 'Modul dihapus dari sekolah');
    }
}
