<?php

namespace App\Observers;

use App\Models\MasterSekolah;

class MasterSekolahObserver
{
    public function created(MasterSekolah $m): void
    {
        log_activity('school.create', $m, [], [
            'nama' => $m->nama_sekolah ?? null,
            'id'   => $m->id,
        ], $m->id, 'Sekolah ditambahkan');
    }

    public function updated(MasterSekolah $m): void
    {
        if ($m->wasChanged('stage') || $m->wasChanged('status_klien')) {
            log_activity('stage.change', $m, [
                'stage' => $m->getOriginal('stage') ?? $m->getOriginal('status_klien'),
            ], [
                'stage' => $m->stage ?? $m->status_klien,
                'id'    => $m->id,
            ], $m->id, 'Perubahan stage sekolah');
        } else {
            log_activity('school.update', $m, [], ['id' => $m->id], $m->id, 'Data sekolah diperbarui');
        }
    }
}
