<?php

namespace App\Observers;

use App\Models\Mou;

class MouObserver
{
    public function created(Mou $m): void
    {
        log_activity('mou.upload', $m, [], [
            'file'    => $m->file_name ?? null,
            'tanggal' => $m->tanggal_mou ?? null,
            'id'      => $m->id,
        ], $m->master_sekolah_id, 'MOU diunggah');
    }

    public function updated(Mou $m): void
    {
        log_activity('mou.update', $m, [], ['id' => $m->id], $m->master_sekolah_id, 'MOU diperbarui');
    }

    public function deleted(Mou $m): void
    {
        log_activity('mou.delete', $m, [], ['id' => $m->id], $m->master_sekolah_id, 'MOU dihapus');
    }
}
