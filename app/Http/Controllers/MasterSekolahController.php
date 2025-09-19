<?php

namespace App\Http\Controllers;

use App\Models\MasterSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterSekolahController extends Controller
{
    public function index(Request $request)
    {
        $q = MasterSekolah::query();

        // status yang dianggap selesai
        $doneStates = ['done','selesai','complete','completed','ended'];

        // total modul & modul selesai
        $q->withCount([
            'penggunaanModul as total_modul',
            'penggunaanModul as modul_done' => function ($qq) use ($doneStates) {
                $qq->where(function ($w) use ($doneStates) {
                    $w->whereIn(DB::raw("LOWER(COALESCE(penggunaan_modul.status, ''))"), $doneStates)
                      ->orWhereNotNull('penggunaan_modul.finished_at');
                });
            },
        ]);

        // Alias: status => stage
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

        // Filter stage langsung
        if ($request->filled('stage')) {
            $q->where('stage', (int) $request->integer('stage'));
        }

        // Filter MOU & TTD (biarkan seperti sebelumnya jika memang ada scope hasMou/hasTtd)
        if ($request->filled('mou')) {
            $q->{$request->mou === 'yes' ? 'hasMou' : 'hasMou'}($request->mou === 'yes');
        }
        if ($request->filled('ttd')) {
            $q->{$request->ttd === 'yes' ? 'hasTtd' : 'hasTtd'}($request->ttd === 'yes');
        }

        // Pencarian teks
        if ($request->filled('q')) {
            $s   = trim($request->q);
            $op  = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $pat = "%{$s}%";
            $q->where(function ($w) use ($op, $pat) {
                $w->where('nama_sekolah', $op, $pat)
                  ->orWhere('alamat',      $op, $pat)
                  ->orWhere('narahubung',  $op, $pat)
                  ->orWhere('no_hp',       $op, $pat);
            });
        }

        $rows = $q->withCount('aktivitas')
                  ->latest()
                  ->paginate((int) $request->input('per_page', 15))
                  ->withQueryString();

        $stageOptions = [
            MasterSekolah::ST_CALON      => 'Calon',
            MasterSekolah::ST_PROSPEK    => 'Prospek',
            MasterSekolah::ST_NEGOSIASI  => 'Negosiasi',
            MasterSekolah::ST_MOU        => 'MOU',
            MasterSekolah::ST_KLIEN      => 'Klien',
        ];

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

        $payload['status_klien'] = $payload['status_klien'] ?? 'calon';
        $stageMap = [
            'calon'   => MasterSekolah::ST_CALON,
            'prospek' => MasterSekolah::ST_PROSPEK,
            'klien'   => MasterSekolah::ST_KLIEN,
        ];
        $payload['stage'] = $stageMap[$payload['status_klien']] ?? MasterSekolah::ST_CALON;

        $row = MasterSekolah::create($payload);

        return redirect()->route('master.index')->with('ok', "Sekolah #{$row->id} berhasil ditambahkan.");
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
     * Opsi B: semua akses /master-sekolah/{id} diarahkan ke halaman aktivitas per-sekolah.
     */
    public function show(MasterSekolah $master)
    {
        return redirect()->route('master.aktivitas.index', $master->id);
    }

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
