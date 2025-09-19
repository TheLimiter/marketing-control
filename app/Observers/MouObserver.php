<?php

namespace App\Observers;

use App\Models\Mou;
use Illuminate\Support\Arr;

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
        $dirty   = array_keys($m->getDirty());
        $watched = ['file_name','file_path','tanggal_mou','keterangan'];
        $changed = array_values(array_intersect($dirty, $watched));

        if (!empty($changed)) {
            log_activity('mou.update', $m,
                Arr::only($m->getOriginal(), $changed),
                Arr::only($m->getAttributes(), $changed),
                $m->master_sekolah_id,
                'MOU diperbarui'
            );
        }
    }

    public function deleted(Mou $m): void
    {
        log_activity('mou.delete', $m, [], ['id' => $m->id], $m->master_sekolah_id, 'MOU dihapus');
    }
}
