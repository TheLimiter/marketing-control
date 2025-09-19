<?php

namespace App\Http\Controllers;

use App\Models\MasterSekolah;
use App\Models\AktivitasProspek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MouController extends Controller
{
    public function form(MasterSekolah $master)
    {
        // $doc dipakai oleh Blade lama
        $doc = $master;
        return view('master.mou_form', compact('master', 'doc'));
    }

    public function save(Request $request, MasterSekolah $master)
    {
        $data = $request->validate([
            'mou'         => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:5120'], // 5 MB
            'ttd_status'  => ['nullable','boolean'],
            'catatan'     => ['nullable','string','max:2000'],
            'next_action' => ['nullable','in:stay,billing'], // << opsi redirect
        ]);

        $changes = [];

        // Upload file
        if ($request->hasFile('mou')) {
            $newPath = $request->file('mou')->store('mou', 'public');

            if ($master->mou_path && Storage::disk('public')->exists($master->mou_path)) {
                Storage::disk('public')->delete($master->mou_path);
            }

            $master->mou_path = $newPath;
            $changes[] = 'upload MOU';
        }

        // Checkbox TTD
        $ttd = $request->boolean('ttd_status');
        if ($ttd !== (bool) $master->ttd_status) {
            $master->ttd_status = $ttd;
            $changes[] = 'TTD ' . ($ttd ? 'ON' : 'OFF');
        }

        // Catatan
        if (array_key_exists('catatan', $data)) {
            $master->mou_catatan = $data['catatan'];
        }

        $master->save();

        // Jejak aktivitas bila ada perubahan
        if (!empty($changes)) {
            AktivitasProspek::create([
                'master_sekolah_id' => $master->id,
                'tanggal'           => now(),
                'jenis'             => 'mou_update',
                'hasil'             => implode(', ', $changes),
                'catatan'           => $data['catatan'] ?? null,
                'created_by'        => auth()->id(),
            ]);
        }

        // Redirect
        if ($request->input('next_action') === 'billing') {
            // arahkan ke form Buat Tagihan sambil membawa master_sekolah_id
            return redirect()
                ->route('tagihan.create', ['master_sekolah_id' => $master->id])
                ->with('ok', 'MOU/TTD tersimpan. Silakan atur tagihan.');
        }

        // default: kembali
        return redirect()
            ->route('master.index')
            ->with('ok', 'MOU/TTD tersimpan.');
    }
}
