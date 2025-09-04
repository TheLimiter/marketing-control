<?php

namespace App\Http\Controllers;

use App\Models\MasterSekolah;
use App\Models\AktivitasProspek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\MasterSekolah as MS;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon; // <- Tambahkan ini

class AktivitasController extends Controller
{
    /**
     * Query dasar untuk halaman GLOBAL.
     * Query string yang didukung:
     * - q      : cari di hasil/catatan
     * - school : cari nama sekolah
     * - jenis  : like
     * - from/to: rentang tanggal
     * - stage  : stage SEKARang pada master
     * - user_id: filter by id user pembuat
     * - oleh   : filter by nama user pembuat (LIKE)
     */
    private function baseAktivitasQuery(Request $r)
    {
        $q = AktivitasProspek::query()
            ->with(['master:id,nama_sekolah,stage', 'creator:id,name', 'files:id,aktivitas_id,original_name,size'])
            ->orderByDesc('tanggal')->orderByDesc('id');

        // Rentang tanggal
        if ($r->filled('from')) {
            $q->whereDate('tanggal', '>=', $r->date('from'));
        }
        if ($r->filled('to')) {
            $q->whereDate('tanggal', '<=', $r->date('to'));
        }

        // Jenis (like)
        if ($r->filled('jenis')) {
            $q->where('jenis', 'like', '%'.trim($r->jenis).'%');
        }

        // Filter stage SEKARang (di tabel master)
        if ($r->filled('stage')) {
            $q->whereHas('master', fn($m) => $m->where('stage', (int) $r->stage));
        }

        // Cari sekolah
        if ($r->filled('school')) {
            $s = trim($r->school);
            $q->whereHas('master', fn($m) => $m->where('nama_sekolah', 'like', "%{$s}%"));
        }

        // Cari di hasil/catatan
        if ($r->filled('q')) {
            $s = trim($r->q);
            $q->where(fn($w) =>
                $w->where('hasil','like',"%{$s}%")
                  ->orWhere('catatan','like',"%{$s}%")
            );
        }

        // by user id
        if ($r->filled('user_id')) {
            $q->where('created_by', (int) $r->user_id);
        }

        // NEW: by user name (oleh)
        if ($r->filled('oleh')) {
            $name = trim($r->oleh);
            $q->whereHas('creator', function($w) use ($name) {
                $w->where('name','like','%'.$name.'%');
            });
        }

        return $q;
    }

    /**
     * Halaman GLOBAL: /aktivitas
     */
    public function all(Request $request)
    {
        $q = $this->baseAktivitasQuery($request);

        // Page size
        $per = (int) $request->get('per', 25);
        $per = in_array($per, [15,25,50,100]) ? $per : 25;

        // Sorting
        $sort = $request->get('sort','tanggal');
        $dir  = strtolower($request->get('dir','desc')) === 'asc' ? 'asc' : 'desc';

        // Hapus order bawaan
        $q->reorder();

        // NEW: sort by creator_name (users.name)
        if ($sort === 'creator_name') {
            $table = (new AktivitasProspek())->getTable(); // aman kalau nama tabel custom
            $q->leftJoin('users', 'users.id', '=', $table.'.created_by')
              ->select($table.'.*')
              ->orderBy('users.name', $dir);
        } else {
            $allowed = ['tanggal','jenis','created_at'];
            if (! in_array($sort, $allowed)) $sort = 'tanggal';
            $q->orderBy($sort, $dir);
        }

        $items = $q->paginate($per)->withQueryString();

        $stageOptions = [
            MS::ST_CALON      => 'Calon',
            MS::ST_PROSPEK    => 'Prospek',
            MS::ST_NEGOSIASI  => 'Negosiasi',
            MS::ST_MOU        => 'MOU',
            MS::ST_KLIEN      => 'Klien',
        ];

        $distinctJenis = AktivitasProspek::query()
            ->select('jenis')->distinct()->pluck('jenis')->filter()->values();

        $jenisOptions = [];
        foreach ($distinctJenis as $j) {
            $jenisOptions[$j] = ucwords(str_replace('_',' ', $j));
        }

        return view('aktivitas.index', compact('items','stageOptions','jenisOptions'));
    }

    /**
     * Export CSV halaman GLOBAL (menghormati filter yang sama).
     */
    public function export(Request $request): StreamedResponse
    {
        $rows = $this->baseAktivitasQuery($request)->limit(50000)->get(); // limit aman

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal','Sekolah','Jenis','Hasil','Catatan','Oleh']);
            foreach ($rows as $r) {
                $jenisDisp = ucwords(str_replace('_',' ', (string) $r->jenis));
                $hasilDisp = (string) $r->hasil;

                fputcsv($out, [
                    optional($r->tanggal)->format('Y-m-d'),
                    optional($r->master)->nama_sekolah,
                    $jenisDisp,
                    $hasilDisp,
                    (string) $r->catatan,
                    optional($r->creator)->name,
                ]);
            }
            fclose($out);
        };

        $filename = 'aktivitas_' . now()->format('Ymd_His') . '.csv';
        return Response::streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Halaman PER-SEKOLAH: /master-sekolah/{master}/aktivitas
     */
    public function index(MasterSekolah $master, Request $r)
    {
        $q = AktivitasProspek::query()
            ->where('master_sekolah_id', $master->id)
            ->with(['creator:id,name','files:id,aktivitas_id,original_name,size']);

        // Filter text hasil/catatan
        if ($s = trim((string) $r->get('q', ''))) {
            $q->where(fn($w) =>
                $w->where('hasil','like',"%{$s}%")
                  ->orWhere('catatan','like',"%{$s}%")
            );
        }
        // Jenis
        if ($jenis = $r->get('jenis')) {
            $q->where('jenis', 'like', '%'.trim($jenis).'%');
        }
        // Rentang tanggal
        if ($from = $r->get('from')) {
            $q->whereDate('tanggal', '>=', $from);
        }
        if ($to = $r->get('to')) {
            $q->whereDate('tanggal', '<=', $to);
        }
        // NEW: filter oleh (nama user)
        if ($r->filled('oleh')) {
            $oleh = trim($r->input('oleh'));
            $q->whereHas('creator', function($w) use ($oleh) {
                $w->where('name','like','%'.$oleh.'%');
            });
        }

        // Page size
        $per = (int) $r->get('per', 25);
        $per = in_array($per, [15,25,50,100]) ? $per : 25;

        // Sorting
        $sort = $r->get('sort','tanggal');
        $dir  = strtolower($r->get('dir','desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'creator_name') {
            $table = (new AktivitasProspek())->getTable();
            $q->leftJoin('users','users.id','=',$table.'.created_by')
              ->select($table.'.*')
              ->orderBy('users.name',$dir);
        } else {
            $allowed = ['tanggal','jenis','created_at'];
            if (! in_array($sort, $allowed)) $sort = 'tanggal';
            $q->orderBy($sort, $dir);
        }

        // Tambahkan tie-breaker id desc
        if ($sort !== 'id') {
            $q->orderBy('id', 'desc');
        }

        $items = $q->paginate($per)->withQueryString();

        // Opsi dropdown jenis (bila diperlukan)
        $jenisOptions = [
            'kunjungan'      => 'Kunjungan',
            'meeting'        => 'Meeting',
            'follow_up'      => 'Follow Up',
            'mou_update'     => 'MOU / TTD',
            'stage_change'   => 'Perubahan Tahap',
            'modul_progress' => 'Progress Modul',
            'modul_done'     => 'Modul Selesai',
            'modul_reopen'   => 'Modul Reopen',
            'modul_attach'   => 'Lampiran Modul',
            'lainnya'        => 'Lainnya',
        ];

        // NEW (opsional): datalist nama user untuk auto-suggest
        $creatorIds = AktivitasProspek::where('master_sekolah_id',$master->id)
                         ->distinct()->pluck('created_by')->filter();
        $creatorOptions = collect();
        if ($creatorIds->isNotEmpty()) {
            $creatorOptions = User::whereIn('id',$creatorIds)->orderBy('name')->pluck('name');
        }

        return view('master.aktivitas_index', compact('master','items','jenisOptions','creatorOptions'));
    }

    /**
     * Tambah aktivitas untuk satu sekolah.
     */
    public function store(Request $request, MasterSekolah $master)
    {
        $payload = $request->validate([
            // 'tanggal' JANGAN diterima dari user untuk default demo
            'jenis'   => ['required','string','max:100'],
            'hasil'   => ['required','string','max:150'],
            'catatan' => ['nullable','string'],
            'files.*' => ['file','max:5120', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx'],
        ]);

        $payload['master_sekolah_id'] = $master->id;
        $payload['created_by']        = auth()->id();
        $payload['tanggal']           = now(); // <- kunci dari server (detik ini)

        // (Opsional) jika ingin admin bisa override tanggal (NON-wajib untuk demo)
        // if ($request->user()->hasRole('admin') && $request->filled('tanggal')) {
        //     $request->validate(['tanggal' => ['date','before_or_equal:now']]);
        //     $payload['tanggal'] = Carbon::parse($request->input('tanggal'));
        // }

        $aktivitas = AktivitasProspek::create($payload);

        // Simpan lampiran kalau ada
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $f) {
                if (!$f->isValid()) continue;
                $path = $f->store('aktivitas', 'public');
                $aktivitas->files()->create([
                    'path'          => $path,
                    'original_name' => $f->getClientOriginalName(),
                    'size'          => $f->getSize(),
                    'mime'          => $f->getMimeType(),
                ]);
            }
        }

        return back()->with('ok', 'Aktivitas berhasil ditambahkan.');
    }

    public function destroy(MasterSekolah $master, AktivitasProspek $aktivitas)
    {
        abort_unless($aktivitas->master_sekolah_id === $master->id, 404);
        $this->authorize('delete', $aktivitas);
        $aktivitas->delete(); // SoftDeletes di model
        return back()->with('ok', 'Aktivitas dipindahkan ke Riwayat.');
    }

    // ====== Trash / Restore / Force Delete ======

    public function trash(MasterSekolah $master)
    {
        $items = $master->aktivitas()
            ->onlyTrashed()
            ->with('creator')
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate(15);

        $jenisOptions = [
            'kunjungan' => 'Kunjungan',
            'lainnya'   => 'Lainnya',
        ];

        return view('master.aktivitas_index', [
            'master' => $master,
            'items'  => $items,
            'jenisOptions' => $jenisOptions,
            'showTrash' => true,
        ]);
    }

    public function restore(MasterSekolah $master, $aktivitas)
    {
        $row = AktivitasProspek::withTrashed()
            ->where('id', $aktivitas)
            ->where('master_sekolah_id', $master->id)
            ->firstOrFail();

        $row->restore();
        return back()->with('ok', 'Aktivitas berhasil dipulihkan.');
    }

    public function forceDelete(MasterSekolah $master, $aktivitas)
    {
        abort_unless(auth()->check() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin(), 403);

        $row = AktivitasProspek::withTrashed()
            ->where('id', $aktivitas)
            ->where('master_sekolah_id', $master->id)
            ->firstOrFail();

        $this->authorize('forceDelete', $row);
        $row->forceDelete();
        return back()->with('ok', 'Aktivitas dihapus permanen.');
    }

    public function bulk(Request $r)
    {
        // Hanya admin
        if (! $r->user()->hasRole('admin')) {
            return back()->with('err', 'Aksi massal hanya untuk admin.');
        }

        $data = $r->validate([
            'action' => 'required|in:delete,export',
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
        ]);

        $ids = array_unique($data['ids']);

        if ($data['action'] === 'delete') {
            AktivitasProspek::whereIn('id', $ids)->delete(); // Soft delete
            return back()->with('ok', count($ids).' aktivitas dihapus.');
        }

        // EXPORT CSV TERPILIH
        $rows = AktivitasProspek::with(['master:id,nama_sekolah', 'creator:id,name'])
            ->whereIn('id', $ids)
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->get();

        $filename = 'aktivitas_selected_'.now()->format('Ymd_His').'.csv';

        return \Response::streamDownload(function() use ($rows){
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Tanggal','Sekolah','Jenis','Hasil','Catatan','Oleh']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    optional($r->tanggal)->format('Y-m-d'),
                    optional($r->master)->nama_sekolah,
                    $r->jenis,
                    $r->hasil,
                    (string) $r->catatan,
                    optional($r->creator)->name,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function bulkPerSekolah(Request $r, MasterSekolah $master)
    {
        // Hanya admin
        if (! $r->user()->hasRole('admin')) {
            return back()->with('err', 'Aksi massal hanya untuk admin.');
        }

        $data = $r->validate([
            'action' => 'required|in:delete,export',
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
        ]);

        $ids = array_unique($data['ids']);

        $q = AktivitasProspek::where('master_sekolah_id', $master->id)
            ->whereIn('id', $ids);

        if ($data['action'] === 'delete') {
            $q->delete();
            return back()->with('ok', count($ids).' aktivitas dihapus.');
        }

        $rows = $q->with(['creator:id,name'])->orderByDesc('tanggal')->orderByDesc('id')->get();
        $filename = 'aktivitas_'.$master->id.'_selected_'.now()->format('Ymd_His').'.csv';

        return \Response::streamDownload(function() use ($rows){
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Tanggal','Jenis','Hasil','Catatan','Oleh']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    optional($r->tanggal)->format('Y-m-d'),
                    $r->jenis,
                    $r->hasil,
                    (string) $r->catatan,
                    optional($r->creator)->name,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
