<?php

namespace App\Observers;

use App\Models\TagihanKlien;
use Illuminate\Support\Arr;

class TagihanObserver
{
    public function created(TagihanKlien $m): void
    {
        log_activity('tagihan.create', $m, [], [
            'nominal' => $m->nominal,
            'tanggal' => $m->tanggal_tagihan,
            'status'  => $m->status,
            'id'      => $m->id,
        ], $m->master_sekolah_id, 'Tagihan dibuat');
    }

    public function updated(TagihanKlien $m): void
    {
        if ($m->wasChanged('status')) {
            // Pakai slug lama kamu atau mapping paid/unpaid
            log_activity('tagihan.status_change', $m, [
                'status' => $m->getOriginal('status'),
            ], [
                'status' => $m->status,
                'id'     => $m->id,
            ], $m->master_sekolah_id, 'Status tagihan berubah');
            return;
        }

        $dirty   = array_keys($m->getDirty());
        $watched = ['nominal','tanggal_tagihan','catatan'];
        $changed = array_values(array_intersect($dirty, $watched));

        if (!empty($changed)) {
            log_activity('tagihan.update', $m,
                Arr::only($m->getOriginal(), $changed),
                Arr::only($m->getAttributes(), $changed),
                $m->master_sekolah_id,
                'Tagihan diperbarui'
            );
        }
    }

    public function deleted(TagihanKlien $m): void
    {
        log_activity('tagihan.delete', $m, [], ['id' => $m->id], $m->master_sekolah_id, 'Tagihan dihapus');
    }
}
