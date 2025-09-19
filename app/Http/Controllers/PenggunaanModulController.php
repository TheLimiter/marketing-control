<?php

namespace App\Http\Controllers;

use App\Models\PenggunaanModul;
use App\Models\MasterSekolah as MS;
use App\Models\MasterSekolah;
use App\Models\Modul;
use App\Models\AktivitasProspek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PenggunaanModulController extends Controller
{
    public function index(Request $r)
    {
        $items = PenggunaanModul::with(['master:id,nama_sekolah,stage', 'modul:id,nama'])
            ->when($r->filled('q'), fn ($q) => $q->whereHas('master', fn ($m) => $m->where('nama_sekolah', 'like', '%'.$r->q.'%')))
            ->when($r->filled('modul_id'), fn ($q) => $q->where('modul_id', (int) $r->modul_id))
            ->when($r->filled('lisensi'), function ($q) use ($r) {
                if ($r->lisensi === 'official') $q->where('is_official', 1);
                if ($r->lisensi === 'trial')  $q->where('is_official', 0);
            })
            ->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))
            ->when($r->filled('stage'), fn ($q) => $q->whereHas('master', fn ($m) => $m->where('stage', (int) $r->stage)))
            ->orderByDesc('last_used_at')
            ->orderBy('mulai_tanggal')
            ->paginate(15)
            ->withQueryString();

        $modulOptions = Modul::orderBy('nama')->get(['id', 'nama']);
        $stageOptions = [
            MS::ST_CALON => 'Calon',
            MS::ST_PROSPEK => 'Prospek',
            MS::ST_NEGOSIASI => 'Negosiasi',
            MS::ST_MOU => 'MOU',
            MS::ST_KLIEN => 'Klien',
        ];

        return view('penggunaan_modul.index', compact('items', 'modulOptions', 'stageOptions'));
    }

    public function create()
    {
        return view('penggunaan_modul.create', [
            'sekolah' => MS::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'modul'   => Modul::aktif()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function prefill(Request $r)
    {
        $masterId = (int) $r->query('master_id');
        abort_unless($masterId, 404);

        $last = PenggunaanModul::where('master_sekolah_id', $masterId)
            ->latest('created_at')->first();

        return response()->json([
            'ok'   => true,
            'data' => [
                'pengguna_nama'   => $last->pengguna_nama ?? '',
                'pengguna_kontak' => $last->pengguna_kontak ?? '',
                'is_official'     => (bool) ($last->is_official ?? false),
                'mulai_tanggal'   => now()->toDateString(),
            ],
        ]);
    }

    public function storeGlobal(Request $r)
    {
        $r->validate([
            'master_sekolah_id' => ['required', 'integer', 'exists:master_sekolah,id'],
        ]);

        $master = MasterSekolah::findOrFail((int) $r->master_sekolah_id);
        return $this->store($r, $master);
    }

    public function store(Request $r, \App\Models\MasterSekolah $master)
    {
        $data = $r->validate([
            'modul_id'        => ['required','integer','exists:modul,id',
                \Illuminate\Validation\Rule::unique('penggunaan_modul','modul_id')
                    ->where(fn($q)=>$q->where('master_sekolah_id',$master->id))
            ],
            'mulai_tanggal'   => ['nullable','date'],
            'akhir_tanggal'   => ['nullable','date','after_or_equal:mulai_tanggal'],
            'is_official'     => ['nullable','boolean'],
            'pengguna_nama'   => ['nullable','string','max:120'],
            'pengguna_kontak' => ['nullable','string','max:120'],
            'catatan'         => ['nullable','string','max:1000'],
        ], ['modul_id.unique'=>'Modul ini sudah terpasang pada sekolah tersebut.']);

        $last  = \App\Models\PenggunaanModul::where('master_sekolah_id',$master->id)->latest('created_at')->first();
        $mulai = $data['mulai_tanggal'] ?? now()->toDateString();

        // SIMPAN PM (perbaiki: pakai kolom 'catatan', bukan 'notes')
        $pm = \App\Models\PenggunaanModul::create([
            'master_sekolah_id' => $master->id,
            'modul_id'          => (int)$data['modul_id'],
            'status'            => \App\Models\PenggunaanModul::ST_ATTACHED,
            'mulai_tanggal'     => $mulai,
            'akhir_tanggal'     => $data['akhir_tanggal'] ?? null,
            'is_official'       => $r->boolean('is_official', (bool) optional($last)->is_official),
            'pengguna_nama'     => $data['pengguna_nama']   ?? optional($last)->pengguna_nama,
            'pengguna_kontak'   => $data['pengguna_kontak'] ?? optional($last)->pengguna_kontak,
            'catatan'           => $data['catatan'] ?? null,   // <-- FIX DISINI
            'created_by'        => auth()->id(),
            'updated_by'        => auth()->id(),
        ]);

        // CATAT AKTIVITAS (global & per-sekolah)
        \App\Models\AktivitasProspek::create([
            'master_sekolah_id' => $master->id,
            'tanggal'           => now(),
            'jenis'             => 'module_assign',                 // konsisten dg model
            'hasil'             => $pm->modul->nama ?? 'Modul',     // ambil nama modul
            'catatan'           => $data['catatan'] ?? null,
            'created_by'        => auth()->id(),
        ]);

        return redirect()->route('penggunaan-modul.index')->with('ok','Modul berhasil ditautkan.');
    }

    public function createBatch(Request $r)
    {
        $modul = Modul::query()
            ->when($r->boolean('only_active', true), fn ($q) => $q->where('active', true))
            ->orderBy('nama')->get(['id', 'nama']);

        $schoolId = $r->integer('school') ?: null;

        return view('penggunaan_modul.batch', compact('modul', 'schoolId'));
    }

    public function storeBatch(Request $r)
    {
        $data = $r->validate([
            'master_sekolah_id' => 'required|exists:master_sekolah,id',
            'modul_ids'         => 'required|array|min:1',
            'modul_ids.*'       => 'distinct|exists:modul,id',
        ]);

        $schoolId = (int) $data['master_sekolah_id'];
        $modulIds = array_values(array_unique($data['modul_ids']));

        $created = 0;
        $skipped = 0;
        $rows    = [];

        DB::transaction(function () use ($schoolId, $modulIds, &$created, &$skipped, &$rows) {
            foreach ($modulIds as $mid) {
                $pm = PenggunaanModul::firstOrCreate(
                    ['master_sekolah_id' => $schoolId, 'modul_id' => $mid],
                    []
                );

                if ($pm->wasRecentlyCreated) {
                    $created++;
                    $rows[] = $pm;
                } else {
                    $skipped++;
                }
            }
        });

        log_activity(
            'modul.use_batch',
            null,
            [],
            ['total_added' => $created, 'skipped_exists' => $skipped, 'modul_ids' => $modulIds],
            $schoolId,
            'Batch modul ditambahkan'
        );

        return redirect()
            ->route('penggunaan-modul.index', ['school' => $schoolId])
            ->with('ok', "Batch selesai: {$created} ditambahkan, {$skipped} dilewati.");
    }

    public function edit(PenggunaanModul $penggunaan_modul)
    {
        return view('penggunaan_modul.edit', [
            'item'    => $penggunaan_modul->load(['master:id,nama_sekolah', 'modul:id,nama']),
            'sekolah' => MS::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
            'modul'   => Modul::aktif()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $r, PenggunaanModul $penggunaan_modul)
    {
        $data = $r->validate([
            'master_sekolah_id' => ['required', 'integer', 'exists:master_sekolah,id'],
            'modul_id'          => ['required', 'integer', 'exists:modul,id'],
            'pengguna_nama'     => ['nullable', 'string', 'max:120'],
            'pengguna_kontak'   => ['nullable', 'string', 'max:120'],
            'mulai_tanggal'     => ['nullable', 'date'],
            'akhir_tanggal'     => ['nullable', 'date', 'after_or_equal:mulai_tanggal'],
            'is_official'       => ['nullable', 'boolean'],
            'status'            => ['sometimes', 'in:active,paused,ended'],
            'catatan'           => ['nullable', 'string'],
        ]);

        if ($r->filled('durasi_hari')) {
            $data['mulai_tanggal'] = now()->toDateString();
            $data['akhir_tanggal'] = now()->addDays(((int) $r->durasi_hari) - 1)->toDateString();
        }

        $dummy        = new PenggunaanModul();
        $statusTarget = $data['status'] ?? $penggunaan_modul->status;

        if (in_array($statusTarget, ['active', 'paused'], true)) {
            if ($dummy->overlaps(
                (int) $data['master_sekolah_id'],
                (int) $data['modul_id'],
                $data['mulai_tanggal'] ?? now()->toDateString(),
                $data['akhir_tanggal'] ?? null,
                $penggunaan_modul->id
            )) {
                return back()->withErrors('Periode bertabrakan dengan penggunaan lain.')->withInput();
            }
        }

        $penggunaan_modul->fill([
            'master_sekolah_id' => (int) $data['master_sekolah_id'],
            'modul_id'          => (int) $data['modul_id'],
            'pengguna_nama'     => $data['pengguna_nama'] ?? null,
            'pengguna_kontak'   => $data['pengguna_kontak'] ?? null,
            'mulai_tanggal'     => $data['mulai_tanggal'] ?? null,
            'akhir_tanggal'     => $data['akhir_tanggal'] ?? null,
            'is_official'       => $r->boolean('is_official'),
            'status'            => $statusTarget,
            'catatan'           => $data['catatan'] ?? null,
            'updated_by'        => auth()->id(),
        ])->save();

        return back()->with('ok', 'Penggunaan modul diperbarui.');
    }

    public function destroy(PenggunaanModul $penggunaan_modul)
    {
        $this->authorize('delete', $penggunaan_modul);
        $penggunaan_modul->delete();
        return back()->with('ok', 'Penggunaan modul dipindahkan ke riwayat.');
    }

    public function useNow(PenggunaanModul $pm)
    {
        $pm->update(['last_used_at' => now()]);

        AktivitasProspek::create([
            'master_sekolah_id' => $pm->master_sekolah_id,
            'tanggal'           => now(),
            'jenis'             => 'module_use',
            'hasil'             => $pm->modul->nama ?? 'Modul',
            'catatan'           => null,
            'created_by'        => auth()->id(),
        ]);

        return back()->with('ok', 'Penggunaan dicatat.');
    }

    public function updateStatus(Request $r, PenggunaanModul $pm)
    {
        $data = $r->validate([
            'status'      => ['nullable', 'in:active,paused,ended'],
            'is_official' => ['nullable', 'boolean'],
        ]);

        if ($r->has('is_official')) {
            $pm->is_official = $r->boolean('is_official');
        }

        if ($r->filled('status')) {
            $pm->status = $data['status'];
            if ($data['status'] === 'ended' && ! $pm->akhir_tanggal) {
                $pm->akhir_tanggal = now()->toDateString();
            }
        }

        $pm->save();

        AktivitasProspek::create([
            'master_sekolah_id' => $pm->master_sekolah_id,
            'tanggal'           => now(),
            'jenis'             => 'module_status',
            'hasil'             => ($pm->is_official ? 'official' : 'trial') . ' / ' . $pm->status,
            'catatan'           => $pm->catatan,
            'created_by'        => auth()->id(),
        ]);

        return back()->with('ok', 'Status diperbarui.');
    }

    public function start(PenggunaanModul $pm)
    {
        $this->authorize('update', $pm);

        if ($pm->status === PenggunaanModul::ST_DONE) {
            return back()->with('err', 'Sudah selesai. Gunakan Reopen bila perlu.');
        }

        $pm->update([
            'status'     => PenggunaanModul::ST_PROGRESS,
            'started_at' => $pm->started_at ?? now(),
        ]);

        return back()->with('ok', 'Progress dimulai.');
    }

    public function done(PenggunaanModul $pm)
    {
        $this->authorize('update', $pm);

        $pm->update([
            'status'      => PenggunaanModul::ST_DONE,
            'finished_at' => now(),
        ]);

        return back()->with('ok', 'Modul ditandai selesai.');
    }

    public function reopen(PenggunaanModul $pm)
    {
        $this->authorize('update', $pm);

        $pm->update([
            'status'      => PenggunaanModul::ST_REOPEN,
            'reopened_at' => now(),
            'finished_at' => null,
        ]);

        return back()->with('ok', 'Modul di-reopen.');
    }

    public function trash()
    {
        $items = PenggunaanModul::onlyTrashed()
            ->with(['master:id,nama_sekolah', 'modul:id,nama'])
            ->latest('deleted_at')
            ->paginate(15);

        return view('penggunaan_modul.trash', compact('items'));
    }

    public function restore($id)
    {
        $row = PenggunaanModul::onlyTrashed()->findOrFail($id);
        $row->restore();

        return back()->with('ok', 'Dipulihkan.');
    }

    public function forceDelete($id)
    {
        $row = PenggunaanModul::onlyTrashed()->findOrFail($id);
        $row->forceDelete();

        return back()->with('ok', 'Dihapus permanen.');
    }
}
