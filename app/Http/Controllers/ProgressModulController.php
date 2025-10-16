<?php

namespace App\Http\Controllers;

use App\Models\AktivitasProspek;
use App\Models\MasterSekolah;
use App\Models\PenggunaanModul;
use App\Models\Modul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressModulController extends Controller
{
    private function logAktivitas(MasterSekolah $master, string $hasil, ?string $catatan = null): void
    {
        $master->aktivitas()->create([
            'tanggal'   => now(),
            'jenis'     => 'modul_progress',
            'hasil'     => $hasil,
            'catatan'   => $catatan,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * List sekolah + ringkasan progress + agregat stage penggunaan modul.
     * Mengembalikan paginator $items (agar sesuai blade).
     */
    public function index(Request $r)
    {
        $search   = trim($r->get('q', ''));
        $today    = now()->toDateString();
        $weekAgo  = now()->subDays(7)->toDateString();
        $perPage  = (int) $r->get('per_page', 15);
        $sort     = $r->get('sort', 'updated_desc'); // updated_desc|updated_asc|progress_desc|progress_asc|school_asc|school_desc
        $status   = $r->get('status');               // done|ontrack|berjalan|baru|belum

        // apakah kolom stage_modul ada di tabel penggunaan_modul
        $hasStageCol = Schema::hasColumn('penggunaan_modul', 'stage_modul');

        // kondisi "done"
        $doneCond = "(LOWER(COALESCE(status,'')) IN ('selesai','done','complete','completed','ended') OR finished_at IS NOT NULL)";

        // ekspresi agregat stage
        if ($hasStageCol) {
            $expDil     = "SUM(CASE WHEN LOWER(COALESCE(stage_modul,'')) = 'dilatih'    THEN 1 ELSE 0 END)";
            $expDidamp  = "SUM(CASE WHEN LOWER(COALESCE(stage_modul,'')) = 'didampingi' THEN 1 ELSE 0 END)";
            $expMandiri = "SUM(CASE WHEN LOWER(COALESCE(stage_modul,'')) = 'mandiri'    THEN 1 ELSE 0 END)";
        } else {
            // fallback heuristik
            $expMandiri = "SUM(CASE WHEN {$doneCond} THEN 1 ELSE 0 END)";
            $expDidamp  = "SUM(CASE WHEN NOT ({$doneCond}) AND mulai_tanggal IS NOT NULL THEN 1 ELSE 0 END)";
            $expDil     = "SUM(CASE WHEN NOT ({$doneCond}) AND (mulai_tanggal IS NULL) THEN 1 ELSE 0 END)";
        }

        $selesaiExpr = "SUM(CASE WHEN {$doneCond} THEN 1 ELSE 0 END)";

        $items = MasterSekolah::query()
            ->select('master_sekolah.*')
            ->withCount(['penggunaanModul as total_modul'])
            ->when($search !== '', fn ($q) => $q->where('nama_sekolah', 'like', "%{$search}%"))
            ->whereHas('penggunaanModul')
            ->addSelect([
                'selesai' => PenggunaanModul::selectRaw($selesaiExpr)
                    ->whereColumn('master_sekolah_id', 'master_sekolah.id'),
                'last_update' => PenggunaanModul::selectRaw('MAX(COALESCE(updated_at, created_at))')
                    ->whereColumn('master_sekolah_id', 'master_sekolah.id'),
                'latest_activity_data' => AktivitasProspek::selectRaw("CONCAT(COALESCE(catatan, ''), '|||', COALESCE(hasil, ''))")
                    ->whereColumn('master_sekolah_id', 'master_sekolah.id')
                    ->where(function ($q2) {
                        $q2->where('jenis', 'like', 'modul_%')
                           ->orWhereIn('jenis', ['modul_progress','modul_done','modul_reopen','modul_attach']);
                    })
                    ->latest('tanggal')
                    ->limit(1),
                'cnt_dilatih'    => PenggunaanModul::selectRaw($expDil)->whereColumn('master_sekolah_id', 'master_sekolah.id'),
                'cnt_didampingi' => PenggunaanModul::selectRaw($expDidamp)->whereColumn('master_sekolah_id', 'master_sekolah.id'),
                'cnt_mandiri'    => PenggunaanModul::selectRaw($expMandiri)->whereColumn('master_sekolah_id', 'master_sekolah.id'),
            ])
            ->orderByDesc('last_update')
            ->paginate($perPage)
            ->withQueryString();

        $collection = $items->getCollection()->map(function ($row) {
            $row->progress_percent = $row->total_modul > 0
                ? (int) round(($row->selesai / $row->total_modul) * 100)
                : 0;
            return $row;
        });
        $items->setCollection($collection);

        // Ambil summary global (tidak terpengaruh paginasi)
        // FIX: Query dioptimalkan untuk performa dan memperbaiki syntax error PostgreSQL
        $summaryQuery = DB::table('penggunaan_modul')
            ->selectRaw('COUNT(DISTINCT master_sekolah_id) as total_sekolah')
            ->selectRaw('COUNT(*) as total_modul')
            ->selectRaw("SUM(CASE WHEN {$doneCond} THEN 1 ELSE 0 END) as total_selesai")
            ->first();

        $totalModul = $summaryQuery->total_modul ?? 0;
        $totalSelesai = $summaryQuery->total_selesai ?? 0;
        $avgProgress = $totalModul > 0 ? round(($totalSelesai / $totalModul) * 100) : 0;

        $summary = [
            'total_modul' => $totalModul,
            'total_selesai' => $totalSelesai,
            'avg_progress' => $avgProgress,
        ];

        return view('progress_modul.index', [
            'items'   => $items,
            'search'  => $search,
            'summary' => $summary,
        ]);
    }

    /** Detail progress modul satu sekolah + daftar aktivitas modul. */
    public function show(MasterSekolah $master, Request $r)
    {
        $today   = now()->startOfDay();
        $weekAgo = $today->copy()->subDays(7);

        $modulCols = Schema::hasColumn('modul','urutan')
            ? ['id','nama','urutan']
            : ['id','nama'];

        $items = PenggunaanModul::with(['modul' => function ($q) use ($modulCols) {
                $q->select($modulCols);
            }])
            ->where('master_sekolah_id', $master->id)
            ->get()
            ->sortBy(function ($x) {
                if (Schema::hasColumn('modul','urutan') && isset($x->modul->urutan)) {
                    return $x->modul->urutan;
                }
                return $x->modul->nama ?? $x->modul_id;
            })
            ->values();

        $selesai    = $items->filter(fn ($x) => $x->isDone())->count();
        $total      = $items->count();
        $aktif      = $items->filter(fn ($x) => $x->isActive())->count();
        $belumAda   = $total - $selesai - $aktif;
        $percent    = $total ? (int) floor(($selesai / $total) * 100) : 0;
        $lastUpdate = optional($items->max('updated_at'));
        $staleDays  = (int) env('PROGRESS_STALE_DAYS', 7);
        $nextItem   = $items->first(fn ($x) => !$x->isDone());

        // === Aktivitas Modul (modul_*) + filter/pagination terpisah ===
        $perAct = (int) $r->get('per_act', 10);

        $logAkt = AktivitasProspek::query()
            ->with([
                'creator:id,name',
                'files:id,aktivitas_id,original_name,size,mime,path',
                'paymentFiles:id,aktivitas_id,original_name,size,mime,path',
            ])
            ->where('master_sekolah_id', $master->id)
            ->where(function($q){
                $q->where('jenis', 'like', 'modul_%')
                  ->orWhereIn('jenis', ['modul_progress','modul_done','modul_reopen','modul_attach']);
            })
            ->when($r->filled('q'), fn($q2) => $q2->where(function($w) use ($r) {
                $s = trim($r->q);
                $w->where('hasil', 'like', "%{$s}%")->orWhere('catatan', 'like', "%{$s}%");
            }))
            ->when($r->filled('from'), fn($q2) => $q2->whereDate('tanggal', '>=', $r->date('from')))
            ->when($r->filled('to'),   fn($q2) => $q2->whereDate('tanggal', '<=', $r->date('to')))
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate($perAct, ['*'], 'act_page')
            ->withQueryString();

        return view('progress_modul.show', compact(
            'master','items','total','selesai','aktif','belumAda',
            'percent','lastUpdate','nextItem','staleDays','logAkt','perAct'
        ));
    }

    /** Matriks 1-9 (tanpa perubahan besar). */
    public function matrix(Request $r)
    {
        $search = trim($r->get('q', ''));

        $modules = Modul::query()
            ->when(Schema::hasColumn('modul', 'urutan'), fn ($q) => $q->orderBy('urutan'))
            ->orderBy('id')->take(9)
            ->get(['id','nama', Schema::hasColumn('modul','urutan') ? 'urutan' : DB::raw('NULL as urutan')]);

        if ($modules->isEmpty()) {
            return view('progress_modul.matrix', [
                'modules'=>collect(),'schools'=>collect(),'grid'=>[],'search'=>$search,
            ])->with('warn','Belum ada data modul.');
        }

        $schools = MasterSekolah::query()
            ->whereHas('penggunaanModul')
            ->when($search !== '', fn ($q) => $q->where('nama_sekolah', 'like', "%{$search}%"))
            ->orderBy('nama_sekolah')
            ->paginate(20)->withQueryString();

        $schoolIds = $schools->pluck('id')->all();
        $moduleIds = $modules->pluck('id')->all();

        $usages = PenggunaanModul::query()
            ->whereIn('master_sekolah_id', $schoolIds)
            ->whereIn('modul_id', $moduleIds)
            ->get()
            ->groupBy(['master_sekolah_id','modul_id']);

        $grid = [];
        foreach ($schoolIds as $sid) {
            foreach ($moduleIds as $mid) {
                $pm   = optional($usages[$sid][$mid] ?? collect())->first();
                $done = $pm ? $pm->isDone() : false;
                $grid[$sid][$mid] = ['pm'=>$pm,'done'=>$done];
            }
        }

        return view('progress_modul.matrix', compact('modules','schools','grid','search'));
    }

    /** Buat baris penggunaan dari tombol + pada matriks. */
    public function attach(MasterSekolah $master, Modul $modul)
    {
        $exists = PenggunaanModul::where('master_sekolah_id', $master->id)
            ->where('modul_id', $modul->id)->exists();

        if ($exists) return back()->with('warn','Baris penggunaan modul sudah ada.');

        PenggunaanModul::create([
            'master_sekolah_id' => $master->id,
            'modul_id'          => $modul->id,
            'status'            => PenggunaanModul::ST_PROGRESS,
            'mulai_tanggal'     => now(),
            'is_official'       => false,
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id(),
        ]);

        $this->logAktivitas($master, 'Mulai '.$modul->nama, 'Row penggunaan modul dibuat.');
        return back()->with('ok','Baris penggunaan modul dibuat.');
    }

    /** Toggle centang modul (done / undo). */
    public function toggle(MasterSekolah $master, PenggunaanModul $pm)
    {
        if ($pm->master_sekolah_id !== $master->id) abort(404);

        if ($pm->isDone()) {
            $pm->status = PenggunaanModul::ST_PROGRESS;
            $pm->finished_at = null;
        } else {
            $pm->status = PenggunaanModul::ST_DONE;
            $pm->finished_at = now();
        }
        $pm->save();

        $label = $pm->modul->nama ?? ('Modul #'.$pm->modul_id);
        $this->logAktivitas($master, ($pm->isDone() ? 'Selesai ' : 'Batalkan selesai ').$label);

        return back()->with('ok','Status progress diperbarui.');
    }

    /** Update tanggal mulai/akhir satu baris. */
    public function updateDates(Request $r, MasterSekolah $master, PenggunaanModul $pm)
    {
        if ($pm->master_sekolah_id !== $master->id) abort(404);

        $data = $r->validate([
            'mulai_tanggal' => ['nullable','date'],
            'akhir_tanggal' => ['nullable','date','after_or_equal:mulai_tanggal'],
        ]);

        $pm->fill([
            'mulai_tanggal' => $data['mulai_tanggal'] ?? $pm->mulai_tanggal,
            'akhir_tanggal' => $data['akhir_tanggal'] ?? null,
        ])->save();

        $label = $pm->modul->nama ?? ('Modul #'.$pm->modul_id);
        $note  = trim(($pm->mulai_tanggal?->format('d/m/Y') ?? '-').' — '.($pm->akhir_tanggal?->format('d/m/Y') ?? '-'));
        $this->logAktivitas($master, 'Ubah tanggal '.$label, $note);

        return back()->with('ok','Tanggal diperbarui.');
    }

    /** NEW: Ubah stage per baris (mirip updateStage di MasterSekolah). */
    public function updateStageModul(Request $r, MasterSekolah $master, PenggunaanModul $pm)
    {
        if ($pm->master_sekolah_id !== $master->id) abort(404);

        $data = $r->validate([
            'stage_modul' => 'required|in:dilatih,didampingi,mandiri',
            'note'        => 'nullable|string|max:500',
        ]);

        $pm->update(['stage_modul' => $data['stage_modul']]);

        $labelModul = $pm->modul->nama ?? ('Modul #'.$pm->modul_id);
        $this->logAktivitas(
            $master,
            "Ubah stage modul: {$labelModul} → ".ucfirst($data['stage_modul']),
            $data['note'] ?? null
        );

        return back()->with('ok','Stage penggunaan modul diperbarui.');
    }

    /** NEW: Ubah stage banyak baris sekaligus milik sekolah. */
    public function bulkUpdateStageModul(Request $r, MasterSekolah $master)
    {
        $data = $r->validate([
            'stage_modul' => 'required|in:dilatih,didampingi,mandiri',
            'modul_ids'   => 'nullable|array',
            'modul_ids.*' => 'integer|exists:penggunaan_modul,id',
            'note'        => 'nullable|string|max:500',
        ]);

        $q = PenggunaanModul::where('master_sekolah_id', $master->id);
        if (!empty($data['modul_ids'])) {
            $q->whereIn('id', $data['modul_ids']);
        }
        $count = $q->update(['stage_modul' => $data['stage_modul']]);

        $this->logAktivitas(
            $master,
            "Bulk ubah stage modul → ".ucfirst($data['stage_modul']),
            ($data['note'] ?? '')." (".$count." baris)"
        );

        return back()->with('ok', "Stage {$count} baris penggunaan diperbarui.");
    }

    /** Quick-add Aktivitas Modul (jenis fixed = modul_progress). */
    public function storeAktivitas(Request $request, MasterSekolah $master)
    {
        $data = $request->validate([
            'hasil'    => ['required','string','max:150'],
            'catatan'  => ['nullable','string'],
            'modul_id' => ['nullable','integer','exists:modul,id'],
            'files.*'  => ['file','max:5120','mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx'],
        ]);

        $hasil = $data['hasil'];
        if (!empty($data['modul_id'])) {
            $m = Modul::find($data['modul_id']);
            if ($m) {
                $hasil = '['.$m->nama.'] '.$hasil;
            }
        }

        $aktivitas = $master->aktivitas()->create([
            'tanggal'    => now(),
            'jenis'      => 'modul_progress',
            'hasil'      => $hasil,
            'catatan'    => $data['catatan'] ?? null,
            'created_by' => auth()->id(),
        ]);

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

        return back()->with('ok', 'Aktivitas modul berhasil ditambahkan.');
    }

    /** Export CSV matriks. */
    public function exportCsv(Request $r): StreamedResponse
    {
        $search  = trim($r->get('q', ''));

        $modules = Modul::query()
            ->when(Schema::hasColumn('modul', 'urutan'), fn ($q) => $q->orderBy('urutan'))
            ->orderBy('id')->take(9)->get(['id','nama']);

        if ($modules->isEmpty()) {
            return response()->streamDownload(function () {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Sekolah']);
                fclose($out);
            }, 'matrix_progress.csv', ['Content-Type' => 'text/csv']);
        }

        $schools = MasterSekolah::query()
            ->whereHas('penggunaanModul')
            ->when($search !== '', fn ($q) => $q->where('nama_sekolah','like',"%{$search}%"))
            ->orderBy('nama_sekolah')
            ->get(['id','nama_sekolah']);

        $usages = PenggunaanModul::query()
            ->whereIn('master_sekolah_id', $schools->pluck('id'))
            ->whereIn('modul_id', $modules->pluck('id'))
            ->get()
            ->groupBy(['master_sekolah_id','modul_id']);

        $filename = 'matrix_progress_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($modules,$schools,$usages) {
            $out = fopen('php://output', 'w');
            $header = ['Sekolah'];
            foreach ($modules as $m) $header[] = $m->id.'. '.$m->nama;
            fputcsv($out, $header);

            foreach ($schools as $s) {
                $line = [$s->nama_sekolah];
                foreach ($modules as $m) {
                    $pm   = optional($usages[$s->id][$m->id] ?? collect())->first();
                    $done = $pm && $pm->isDone();
                    $line[] = $done ? 1 : 0;
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

