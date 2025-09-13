<?php

namespace App\Observers;

use App\Models\PenggunaanModul;

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
        log_activity('modul.use_update', $m, [], ['id' => $m->id], $schoolId, 'Modul penggunaan diperbarui');
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
