<?php

namespace App\Http\Controllers;

use App\Models\Modul;
use App\Models\MasterSekolah;
use App\Models\AktivitasProspek;
use App\Models\TagihanKlien;
use App\Models\User;
use App\Models\MasterSekolah as MS; // Alias untuk konstanta stage
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AktivitasController extends Controller
{
    /**
     * Query dasar untuk halaman GLOBAL.
     */
    private function baseAktivitasQuery(Request $r)
    {
        $q = AktivitasProspek::query()
            ->with([
                'master:id,nama_sekolah,stage,mou_path,ttd_status',
                'creator:id,name',
                'files:id,aktivitas_id,original_name,size,mime,path',
                'paymentFiles:id,aktivitas_id,original_name,size,mime,path',
            ])
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
            $q->where('jenis', 'like', '%' . trim($r->jenis) . '%');
        }

        // Filter stage SEKARANG (di tabel master)
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
                $w->where('hasil', 'like', "%{$s}%")
                    ->orWhere('catatan', 'like', "%{$s}%")
            );
        }

        // by user id
        if ($r->filled('user_id')) {
            $q->where('created_by', (int) $r->user_id);
        }

        // user name (oleh)
        if ($r->filled('oleh')) {
            $name = trim($r->oleh);
            $q->whereHas('creator', function($w) use ($name) {
                $w->where('name', 'like', '%' . $name . '%');
            });
        }

        if ($r->boolean('trashed')) {
            $q->onlyTrashed();
        } elseif ($r->boolean('with_trashed')) {
            $q->withTrashed();
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
        $per = in_array($per, [15, 25, 50, 100]) ? $per : 25;

        // Sorting
        $sort = $request->get('sort', 'tanggal');
        $dir = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Hapus ordering default sebelum apply custom sort
        $q->reorder();

        // sort by creator_name (users.name)
        if ($sort === 'creator_name') {
            $table = (new AktivitasProspek())->getTable();
            $q->leftJoin('users', 'users.id', '=', $table . '.created_by')
                ->select($table . '.*')
                ->orderBy('users.name', $dir)
                ->orderBy($table . '.tanggal', 'desc') // tie-breaker 1
                ->orderBy($table . '.id', 'desc'); // tie-breaker 2
        } else {
            $allowed = ['tanggal', 'jenis', 'created_at', 'id'];
            if (!in_array($sort, $allowed)) {
                $sort = 'tanggal';
            }
            $q->orderBy($sort, $dir)
              ->orderBy('id', 'desc'); // pastikan yang terbaru di atas saat nilai sama
        }

        $items = $q->paginate($per)->withQueryString();

        // PERBAIKAN: Syntax array yang benar
        $stageOptions = [
             MS::ST_CALON   => 'Calon',
             MS::ST_SHB     => 'Sudah Dihubungi',
             MS::ST_SLTH    => 'Sudah Dilatih',
             MS::ST_MOU     => 'MOU Aktif',
             MS::ST_TLMOU   => 'Tindak Lanjut MOU',
             MS::ST_TOLAK   => 'Ditolak',
        ];

        $distinctJenis = AktivitasProspek::query()
            ->select('jenis')->distinct()->pluck('jenis')->filter()->values();

        $jenisOptions = [];
        foreach ($distinctJenis as $j) {
            $jenisOptions[$j] = ucwords(str_replace('_', ' ', $j));
        }

        return view('aktivitas.index', compact('items', 'stageOptions', 'jenisOptions'));
    }

    /**
     * Export CSV halaman GLOBAL
     */
    public function export(Request $request): StreamedResponse
    {
        $rows = $this->baseAktivitasQuery($request)->limit(50000)->get();

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal', 'Sekolah', 'Jenis', 'Hasil', 'Catatan', 'Oleh']);
            foreach ($rows as $r) {
                $jenisDisp = ucwords(str_replace('_', ' ', (string) $r->jenis));
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
            ->with([
                'creator:id,name',
                'files:id,aktivitas_id,original_name,size,mime,path',
                'paymentFiles:id,aktivitas_id,original_name,size,mime,path',
            ]);

        // Filter text hasil/catatan
        if ($s = trim((string) $r->get('q', ''))) {
            $q->where(fn($w) =>
                $w->where('hasil', 'like', "%{$s}%")
                    ->orWhere('catatan', 'like', "%{$s}%")
            );
        }
        // Jenis
        if ($jenis = $r->get('jenis')) {
            $q->where('jenis', 'like', '%' . trim($jenis) . '%');
        }
        // Rentang
        if ($from = $r->get('from')) {
            $q->whereDate('tanggal', '>=', $from);
        }
        if ($to = $r->get('to')) {
            $q->whereDate('tanggal', '<=', $to);
        }
        // filter oleh (user)
        if ($r->filled('oleh')) {
            $oleh = trim($r->input('oleh'));
            $q->whereHas('creator', function($w) use ($oleh) {
                $w->where('name', 'like', '%' . $oleh . '%');
            });
        }

        // Page size
        $per = (int) $r->get('per', 25);
        $per = in_array($per, [15, 25, 50, 100]) ? $per : 25;

        // Sorting
        $sort = $r->get('sort', 'tanggal');
        $dir = strtolower($r->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'creator_name') {
            $table = (new AktivitasProspek())->getTable();
            $q->leftJoin('users', 'users.id', '=', $table . '.created_by')
                ->select($table . '.*')
                ->orderBy('users.name', $dir)
                ->orderBy($table.'.tanggal', 'desc')
                ->orderBy($table.'.id', 'desc');
        } else {
            $allowed = ['tanggal', 'jenis', 'created_at', 'id'];
            if (!in_array($sort, $allowed)) {
                $sort = 'tanggal';
            }
            $q->orderBy($sort, $dir)
              ->orderBy('id', 'desc');
        }

        $items = $q->paginate($per)->withQueryString();

        // Opsi dropdown jenis
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
            'billing_create' => 'Tagihan Dibuat',
            'billing_payment'=> 'Tagihan Dibayar',
            'lainnya'        => 'Lainnya',
        ];

        // Suggest creator name
        $creatorIds = AktivitasProspek::where('master_sekolah_id', $master->id)
            ->distinct()->pluck('created_by')->filter();
            
        $creatorOptions = collect();
        if ($creatorIds->isNotEmpty()) {
            $creatorOptions = User::whereIn('id', $creatorIds)->orderBy('name')->pluck('name');
        }

        $invoiceSummary = TagihanKlien::selectRaw("
            SUM(CASE WHEN COALESCE(terbayar,0) < COALESCE(total,0) THEN 1 ELSE 0 END) AS unpaid_count,
            SUM(CASE WHEN COALESCE(terbayar,0) < COALESCE(total,0)
                     THEN (COALESCE(total,0) - COALESCE(terbayar,0)) ELSE 0 END)       AS unpaid_sum,
            SUM(CASE WHEN COALESCE(terbayar,0) >= COALESCE(total,0) AND COALESCE(total,0) > 0
                     THEN 1 ELSE 0 END)                                                AS paid_count
        ")
        ->where('master_sekolah_id', $master->id)
        ->first();

        return view('master.aktivitas_index', [
            'master' => $master,
            'items'  => $items,
            'jenisOptions' => $jenisOptions,
            'creatorOptions' => $creatorOptions,
            'invoiceSummary' => $invoiceSummary,
        ]);
    }

    /**
     * Tambah aktivitas untuk satu sekolah.
     */
    public function store(Request $request, MasterSekolah $master)
    {
        $payload = $request->validate([
            'jenis'   => ['required','string','max:100'],
            'hasil'   => ['required','string','max:150'],
            'catatan' => ['nullable','string'],
            'modul_id'=> ['nullable','integer','exists:modul,id'],
            'files.*' => ['file','max:10240','mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx'],
        ]);

        // Prefix hasil dengan nama modul (opsional)
        if (!empty($payload['modul_id'])) {
            if ($m = Modul::find($payload['modul_id'])) {
                $payload['hasil'] = '['.$m->nama.'] '.$payload['hasil'];
            }
            unset($payload['modul_id']);
        }

        $payload['master_sekolah_id'] = $master->id;
        $payload['created_by']        = auth()->id();
        $payload['tanggal']           = now();

        $aktivitas = AktivitasProspek::create($payload);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $f) {
                if (!$f->isValid()) continue;
                $path = $f->store('aktivitas','public');
                $aktivitas->files()->create([
                    'path' => $path,
                    'original_name' => $f->getClientOriginalName(),
                    'size' => $f->getSize(),
                    'mime' => $f->getMimeType(),
                ]);
            }
        }

        return back()->with('ok','Aktivitas berhasil ditambahkan.');
    }

    public function destroy(MasterSekolah $master, AktivitasProspek $aktivitas)
    {
        abort_unless($aktivitas->master_sekolah_id === $master->id, 404);
        $this->authorize('delete', $aktivitas);
        $aktivitas->delete();
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
            'lainnya' => 'Lainnya',
        ];

        return view('master.aktivitas_index', [
            'master' => $master,
            'items' => $items,
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
        if (!$r->user()->hasRole('admin')) {
            return back()->with('err', 'Aksi massal hanya untuk admin.');
        }

        $data = $r->validate([
            'action' => 'required|in:delete,export',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = array_unique($data['ids']);

        if ($data['action'] === 'delete') {
            AktivitasProspek::whereIn('id', $ids)->delete();
            return back()->with('ok', count($ids) . ' aktivitas dihapus.');
        }

        // EXPORT CSV TERPILIH
        $rows = AktivitasProspek::with(['master:id,nama_sekolah', 'creator:id,name'])
            ->whereIn('id', $ids)
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->get();

        $filename = 'aktivitas_selected_' . now()->format('Ymd_His') . '.csv';

        return \Response::streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Tanggal', 'Sekolah', 'Jenis', 'Hasil', 'Catatan', 'Oleh']);
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
        if (!$r->user()->hasRole('admin')) {
            return back()->with('err', 'Aksi massal hanya untuk admin.');
        }

        $data = $r->validate([
            'action' => 'required|in:delete,export',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = array_unique($data['ids']);

        $q = AktivitasProspek::where('master_sekolah_id', $master->id)
            ->whereIn('id', $ids);

        if ($data['action'] === 'delete') {
            $q->delete();
            return back()->with('ok', count($ids) . ' aktivitas dihapus.');
        }

        $rows = $q->with(['creator:id,name'])->orderByDesc('tanggal')->orderByDesc('id')->get();
        $filename = 'aktivitas_' . $master->id . '_selected_' . now()->format('Ymd_His') . '.csv';

        return \Response::streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Tanggal', 'Jenis', 'Hasil', 'Catatan', 'Oleh']);
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