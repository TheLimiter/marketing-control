<?php

namespace App\Http\Controllers;

use App\Models\MasterSekolah;
use App\Models\PenggunaanModul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterSekolahController extends Controller
{
    public function index(Request $request)
    {
        $q = MasterSekolah::query();

        // total modul yang di-assign -> penyebut
        $q->withCount([
            'penggunaanModul as total_modul',
            'penggunaanModul as modul_done' => function ($qq) {
                $qq->where(function ($w) {
                    $w->whereIn(DB::raw('LOWER(status)'), ['selesai','done','complete','completed','ended'])
                      ->orWhereNotNull('akhir_tanggal');
                });
            },
        ]);

        // Alias: status=calon|prospek|klien -> filter stage
        if ($request->filled('status')) {
            $map = [
                'calon'   => MasterSekolah::ST_CALON,
                'prospek' => MasterSekolah::ST_PROSPEK,
                'klien'   => MasterSekolah::ST_KLIEN,
            ];
            if (isset($map[$request->status])) {
                $q->where('stage', $map[$request->status]);
            }
        }

        // Filter stage langsung (opsional)
        if ($request->filled('stage')) {
            $q->where('stage', (int) $request->integer('stage'));
        }

        // Filter MOU: yes|no (butuh scope di model)
        if ($request->filled('mou')) {
            if ($request->mou === 'yes') $q->hasMou(true);
            if ($request->mou === 'no')  $q->hasMou(false);
        }

        // Filter TTD: yes|no (butuh scope di model)
        if ($request->filled('ttd')) {
            if ($request->ttd === 'yes') $q->hasTtd(true);
            if ($request->ttd === 'no')  $q->hasTtd(false);
        }

        // Pencarian teks sederhana
        if ($request->filled('q')) {
            $s = trim($request->q);
            $q->where(function ($w) use ($s) {
                $w->where('nama_sekolah', 'like', "%{$s}%")
                  ->orWhere('alamat', 'like', "%{$s}%")
                  ->orWhere('narahubung', 'like', "%{$s}%")
                  ->orWhere('no_hp', 'like', "%{$s}%");
            });
        }

        $rows = $q->withCount('aktivitas')
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();

        $stageOptions = [
            MasterSekolah::ST_CALON     => 'Calon',
            MasterSekolah::ST_PROSPEK   => 'Prospek',
            MasterSekolah::ST_NEGOSIASI => 'Negosiasi',
            MasterSekolah::ST_MOU       => 'MOU',
            MasterSekolah::ST_KLIEN     => 'Klien',
        ];

        // (opsional) paketkan current filters
        $filters = [
            'q'      => $request->q,
            'status' => $request->status,
            'stage'  => $request->stage,
            'mou'    => $request->mou,
            'ttd'    => $request->ttd,
        ];

        return view('master.index', compact('rows', 'stageOptions', 'filters'));
    }

    public function create()
    {
        return view('master.create');
    }

    public function store(Request $r)
    {
        $payload = $r->validate([
            'nama_sekolah'   => 'required|string|max:255',
            'alamat'         => 'nullable|string',
            'no_hp'          => 'nullable|string|max:30',
            'sumber'         => 'nullable|string|max:100',
            'catatan'        => 'nullable|string',
            'jenjang'        => 'nullable|string|max:50',
            'narahubung'     => 'nullable|string|max:100',
            'status_klien'   => 'nullable|in:calon,prospek,klien',
            'tindak_lanjut'  => 'nullable|string',
            'jumlah_siswa'   => 'nullable|integer|min:0',
        ]);

        // Default & mapping stage
        $payload['status_klien'] = $payload['status_klien'] ?? 'calon';
        $stageMap = [
            'calon'   => MasterSekolah::ST_CALON,
            'prospek' => MasterSekolah::ST_PROSPEK,
            'klien'   => MasterSekolah::ST_KLIEN,
        ];
        $payload['stage'] = $stageMap[$payload['status_klien']] ?? MasterSekolah::ST_CALON;

        $row = MasterSekolah::create($payload);

        return redirect()
            ->route('master.index')
            ->with('ok', "Sekolah #{$row->id} berhasil ditambahkan.");
    }

    public function edit(MasterSekolah $master)
    {
        return view('master.edit', compact('master'));
    }

    public function update(Request $r, MasterSekolah $master)
    {
        $payload = $r->validate([
            'nama_sekolah'   => 'required|string|max:255',
            'alamat'         => 'nullable|string',
            'no_hp'          => 'nullable|string|max:30',
            'sumber'         => 'nullable|string|max:100',
            'catatan'        => 'nullable|string',
            'jenjang'        => 'nullable|string|max:50',
            'narahubung'     => 'nullable|string|max:100',
            'tindak_lanjut'  => 'nullable|string',
            'jumlah_siswa'   => 'nullable|integer|min:0',
        ]);

        $master->update($payload);

        return redirect()->route('master.index')->with('ok', 'Data sekolah diperbarui.');
    }

    /**
     * Menampilkan detail lengkap suatu sekolah.
     */
    public function show(\App\Models\MasterSekolah $master)
    {
        // Ambil penggunaan modul + modulnya, urutkan by urutan (fallback id)
        $items = $master->penggunaanModul()
            ->with(['modul:id,nama,urutan'])
            ->get()
            ->sortBy(fn($x) => $x->modul->urutan ?? $x->modul_id)
            ->values();

        $doneStatuses = ['selesai','done','complete','completed','ended'];

        $total   = $items->count();
        $selesai = $items->filter(function($x) use ($doneStatuses){
            $s = strtolower($x->status ?? '');
            return in_array($s, $doneStatuses, true) || !empty($x->akhir_tanggal);
        })->count();
        $aktif   = $items->filter(fn($x)=> strtolower($x->status ?? '')==='aktif' && empty($x->akhir_tanggal))->count();

        $percent = $total ? (int) floor(($selesai/$total)*100) : 0;
        $lastUpdate = optional($items->max('updated_at'));

        $nextItem = $items->first(function($x) use ($doneStatuses){
            $s = strtolower($x->status ?? '');
            return !(in_array($s,$doneStatuses,true) || !empty($x->akhir_tanggal));
        });

        // Aktivitas terbaru (kalau ada)
        $aktivitas = $master->aktivitas()->latest('tanggal')->latest()->take(10)->get();

        return view('master.show', compact(
            'master','items','total','selesai','aktif','percent','lastUpdate','nextItem','aktivitas'
        ));
    }

    // === Stage ===
    public function updateStage(Request $request, MasterSekolah $master)
    {
        $validated = $request->validate([
            'to'   => 'required|integer|in:1,2,3,4,5',
            'note' => 'nullable|string',
        ]);

        try {
            $master->moveToStage((int) $validated['to'], $validated['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Tahapan berhasil diperbarui.');
    }

    public function jadikanKlien(Request $r, MasterSekolah $sekolah)
    {
        $beforeStage = $sekolah->getOriginal('stage');

        $sekolah->stage = MasterSekolah::ST_KLIEN;
        $sekolah->tanggal_menjadi_klien = now();
        $sekolah->save();

        $sekolah->logCustom('prospek.to_klien', ['stage_to' => MasterSekolah::ST_KLIEN], ['stage_from' => $beforeStage]);

        return back()->with('ok', 'Sekolah dijadikan klien.');
    }

    public function destroy(MasterSekolah $master)
    {
        $master->delete();
        return back()->with('ok', 'Data dipindahkan ke Sampah.');
    }

    public function trash()
    {
        $rows = MasterSekolah::onlyTrashed()->latest('deleted_at')->paginate(15);
        return view('master.trash', compact('rows'));
    }

    public function restore($id)
    {
        $row = MasterSekolah::onlyTrashed()->findOrFail($id);
        $row->restore();
        return back()->with('ok', 'Data berhasil dipulihkan.');
    }

    public function forceDelete($id)
    {
        $row = MasterSekolah::onlyTrashed()->findOrFail($id);
        DB::transaction(function () use ($row) {
            $row->forceDelete();
        });
        return back()->with('ok', 'Data dihapus permanen.');
    }
}
