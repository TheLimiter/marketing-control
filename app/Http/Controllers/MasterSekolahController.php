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

        // status yang dianggap selesai untuk progress modul
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

        // === Alias: status (lama) => stage (baru) ===
        // Mendukung nilai lama maupun baru untuk backward-compat.
        if ($request->filled('status')) {
            $status = strtolower(trim($request->status));
            $map = [
                // Lama
                'calon'   => MasterSekolah::ST_CALON,
                'prospek' => MasterSekolah::ST_SHB,     // anggap "prospek" = sudah dihubungi
                'negosiasi' => MasterSekolah::ST_SLTH,  // jika ada yang memakai
                'mou'     => MasterSekolah::ST_MOU,
                'klien'   => MasterSekolah::ST_TLMOU,   // "klien" lama ~ tindak lanjut MOU
                'ditolak' => MasterSekolah::ST_TOLAK,

                // Baru (slug)
                'sudah_dihubungi'   => MasterSekolah::ST_SHB,
                'sudah_dilatih'     => MasterSekolah::ST_SLTH,
                'mou_aktif'         => MasterSekolah::ST_MOU,
                'tindak_lanjut_mou' => MasterSekolah::ST_TLMOU,
            ];
            if (isset($map[$status])) {
                $q->where('stage', $map[$status]);
            }
        }

        // Filter stage langsung
        if ($request->filled('stage')) {
            $q->where('stage', (int) $request->integer('stage'));
        }

        // Filter MOU & TTD (pakai scope yang sudah ada di model)
        if ($request->filled('mou')) {
            $q->hasMou($request->mou === 'yes');
        }
        if ($request->filled('ttd')) {
            $q->hasTtd($request->ttd === 'yes');
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

        // Opsi stage (baru)
        $stageOptions = [
            MasterSekolah::ST_CALON  => 'Calon',
            MasterSekolah::ST_SHB    => 'sudah dihubungi',
            MasterSekolah::ST_SLTH   => 'sudah dilatih',
            MasterSekolah::ST_MOU    => 'MOU Aktif',
            MasterSekolah::ST_TLMOU  => 'Tindak lanjut MOU',
            MasterSekolah::ST_TOLAK  => 'Ditolak',
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
            // status_klien lama tetap dibiarkan untuk kompatibilitas
            'status_klien'   => 'nullable|string|max:50',
            'tindak_lanjut'  => 'nullable|string',
            'jumlah_siswa'   => 'nullable|integer|min:0',
        ]);

        // Default status_klien lama → "calon" jika kosong
        $payload['status_klien'] = $payload['status_klien'] ?? 'calon';

        // Pemetaan lama & baru ke stage baru
        $status = strtolower((string) $payload['status_klien']);
        $stageMap = [
            // Lama
            'calon'     => MasterSekolah::ST_CALON,
            'prospek'   => MasterSekolah::ST_SHB,
            'negosiasi' => MasterSekolah::ST_SLTH,
            'mou'       => MasterSekolah::ST_MOU,
            'klien'     => MasterSekolah::ST_TLMOU,
            'ditolak'   => MasterSekolah::ST_TOLAK,
            // Baru (kalau nanti field ini ikut diisi)
            'sudah_dihubungi'   => MasterSekolah::ST_SHB,
            'sudah_dilatih'     => MasterSekolah::ST_SLTH,
            'mou_aktif'         => MasterSekolah::ST_MOU,
            'tindak_lanjut_mou' => MasterSekolah::ST_TLMOU,
        ];
        $payload['stage'] = $stageMap[$status] ?? MasterSekolah::ST_CALON;

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
            'to'   => 'required|integer|in:1,2,3,4,5,6',
            'note' => 'nullable|string',
        ]);

        try {
            $master->moveToStage((int) $validated['to'], $validated['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Tahapan berhasil diperbarui.');
    }

    // Tetap disediakan untuk kompatibilitas lama (boleh dihapus bila tidak dipakai)
    public function jadikanKlien(Request $r, MasterSekolah $sekolah)
    {
        $beforeStage = $sekolah->getOriginal('stage');

        $sekolah->stage = MasterSekolah::ST_TLMOU; // pada skema baru, "klien" ≈ tindak lanjut MOU
        $sekolah->tanggal_menjadi_klien = now();
        $sekolah->save();

        $sekolah->logCustom('prospek.to_klien', ['stage_to' => MasterSekolah::ST_TLMOU], ['stage_from' => $beforeStage]);

        return back()->with('ok', 'Sekolah dipindahkan ke tindak lanjut MOU.');
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
