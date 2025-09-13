<?php

namespace App\Observers;

use App\Models\TagihanKlien;

class TagihanObserver
{
    public function created(TagihanKlien $m): void
    {
        log_activity('tagihan.create', $m, [], [
            'nominal' => $m->nominal ?? null,
            'tanggal' => $m->tanggal_tagihan ?? null,
            'status'  => $m->status ?? null,
            'id'      => $m->id,
        ], $m->master_sekolah_id, 'Tagihan dibuat');
    }

    public function updated(TagihanKlien $m): void
    {
        if ($m->wasChanged('status')) {
            log_activity('tagihan.status_change', $m, [
                'status' => $m->getOriginal('status'),
            ], [
                'status' => $m->status,
                'id'     => $m->id,
            ], $m->master_sekolah_id, 'Status tagihan berubah');
        }
    }

    public function deleted(TagihanKlien $m): void
    {
        log_activity('tagihan.delete', $m, [], ['id' => $m->id], $m->master_sekolah_id, 'Tagihan dihapus');
    }
}
