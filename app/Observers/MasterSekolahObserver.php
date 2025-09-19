<?php

namespace App\Observers;

use App\Models\MasterSekolah;
use Illuminate\Support\Arr;

class MasterSekolahObserver
{
    public function created(MasterSekolah $m): void
    {
        log_activity('school.create', $m, [], [
            'nama' => $m->nama_sekolah,
            'id'   => $m->id,
        ], $m->id, 'Sekolah ditambahkan');
    }

    public function updated(MasterSekolah $m): void
    {
        $dirty = array_keys($m->getDirty());

        // 1) Stage berubah log khusus
        if (in_array('stage', $dirty, true) || in_array('status_klien', $dirty, true)) {
            log_activity('stage.change', $m, [
                'stage' => $m->getOriginal('stage') ?? $m->getOriginal('status_klien'),
            ], [
                'stage' => $m->stage ?? $m->status_klien,
                'id'    => $m->id,
            ], $m->id, 'Perubahan stage sekolah');
            return;
        }

        // 2) Perubahan data penting log ringkas
        $watched = [
            'nama_sekolah','alamat','narahubung','no_hp','jenjang','sumber',
            'jumlah_siswa','mou_path','ttd_status','tindak_lanjut','catatan'
        ];
        $changedWatched = array_values(array_intersect($dirty, $watched));

        if (!empty($changedWatched)) {
            log_activity('school.update', $m,
                Arr::only($m->getOriginal(), $changedWatched),
                Arr::only($m->getAttributes(), $changedWatched),
                $m->id,
                'Data sekolah diperbarui'
            );
        }
        // kalau tidak ada field penting yang berubah, jangan log apa-apa (hindari noise)
    }
}
