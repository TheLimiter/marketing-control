<?php

namespace App\Http\Controllers;

use App\Models\{MasterSekolah, PenggunaanModul, Modul};
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
            'tanggal'    => now(),
            'jenis'      => 'modul_progress',
            'hasil'      => $hasil,
            'catatan'    => $catatan,
            'created_by' => Auth::id(),
        ]);
    }

    /** List sekolah + ringkasan progress (untuk Progress Modul). */
    public function index(Request $r)
    {
        $search   = trim($r->get('q', ''));
        $today    = now()->toDateString();
        $weekAgo  = now()->subDays(7)->toDateString();
        $perPage  = (int) $r->get('per_page', 15);

        // hitung selesai (tanpa alias ganda)
        $selesaiExpr = "SUM(CASE WHEN (LOWER(COALESCE(status,'')) IN ('selesai','done','complete','completed','ended') OR finished_at IS NOT NULL) THEN 1 ELSE 0 END)";

        $rows = MasterSekolah::query()
            ->select('master_sekolah.*')                      // pastikan kolom sekolah ikut
            ->withCount(['penggunaanModul as total_modul'])   // total baris modul
            ->when($search !== '', fn ($q) =>
                $q->where('nama_sekolah', 'like', "%{$search}%")
            )
            ->whereHas('penggunaanModul') // hanya yg punya penggunaan modul
            ->addSelect([
                // selesai
                'selesai' => PenggunaanModul::selectRaw($selesaiExpr)
                    ->whereColumn('master_sekolah_id', 'master_sekolah.id'),
                // last update
                'last_update' => PenggunaanModul::selectRaw('MAX(COALESCE(updated_at, created_at))')
                    ->whereColumn('master_sekolah_id', 'master_sekolah.id'),
                // merah: ada yang akhir_tanggal < today dan belum ended
                'overdue_cnt' => PenggunaanModul::selectRaw('COUNT(*)')
                    ->whereColumn('master_sekolah_id', 'master_sekolah.id')
                    ->whereNotNull('akhir_tanggal')
                    ->whereDate('akhir_tanggal', '<', $today)
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'ended');
                    }),
                // kuning: mulai_tanggal <= 7 hari yang lalu (apa pun status)
                'aging_cnt' => PenggunaanModul::selectRaw('COUNT(*)')
                    ->whereColumn('master_sekolah_id', 'master_sekolah.id')
                    ->whereNotNull('mulai_tanggal')
                    ->whereDate('mulai_tanggal', '<=', $weekAgo),
            ])
            ->orderByDesc('last_update')
            ->paginate($perPage)
            ->appends($r->query());

        // hitung persen per baris
        $rows->getCollection()->transform(function ($row) {
            $row->progress_percent = $row->total_modul > 0
                ? (int) round(($row->selesai / $row->total_modul) * 100)
                : 0;
            return $row;
        });

        return view('progress_modul.index', compact('rows', 'search'));
    }

    /** Detail progress modul satu sekolah. */
   public function show(MasterSekolah $master)
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

    $uiRows = $items->map(function ($x) use ($master, $today, $weekAgo) {
        $done      = $x->isDone();
        $overdue   = !$done && $x->akhir_tanggal && $x->akhir_tanggal->lt($today) && ($x->status !== 'ended');
        $aging     = !$overdue && $x->mulai_tanggal && $x->mulai_tanggal->lte($weekAgo);
        $cellClass = $overdue ? 'cell-danger' : ($aging ? 'cell-warning' : '');

        $ket = match (true) {
            $x->mulai_tanggal && $x->akhir_tanggal => $x->mulai_tanggal->format('d/m/y').' - '.$x->akhir_tanggal->format('d/m/y'),
            $x->mulai_tanggal => 'Mulai '.$x->mulai_tanggal->format('d/m/y'),
            $x->akhir_tanggal => 'Selesai '.$x->akhir_tanggal->format('d/m/y'),
            default => 'Tambah keterangan',
        };

        return [
            'checked'   => $done,
            'nama'      => $x->modul->nama ?? ('Modul #'.$x->modul_id),
            'nis'       => ($x->modul->urutan ?? null) ?? $x->modul_id, // tetap aman
            'ket'       => $ket,
            'cellClass' => $cellClass,
            'toggle'    => route('progress.toggle', [$master->id, $x->id]),
        ];
    })->all();

    return view('progress_modul.show', compact(
        'master','items','total','selesai','aktif','belumAda',
        'percent','lastUpdate','nextItem','uiRows','staleDays'
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
            'status'            => PenggunaanModul::ST_PROGRESS, // konsisten
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
        $note  = trim(($pm->mulai_tanggal?->format('d/m/Y') ?? '-').''.($pm->akhir_tanggal?->format('d/m/Y') ?? '-'));
        $this->logAktivitas($master, 'Ubah tanggal '.$label, $note);

        return back()->with('ok','Tanggal diperbarui.');
    }

    /** Pastikan baris 1-9 ada semua. */
    public function ensure(MasterSekolah $master)
    {
        $mods = Modul::query()
            ->when(Schema::hasColumn('modul', 'urutan'), fn ($q) => $q->orderBy('urutan'))
            ->orderBy('id')->take(9)->get(['id','nama']);

        if ($mods->isEmpty()) return back()->with('warn','Belum ada data modul.');

        $existing = PenggunaanModul::where('master_sekolah_id',$master->id)
            ->whereIn('modul_id', $mods->pluck('id'))->pluck('modul_id')->all();

        $created = 0;
        foreach ($mods as $m) {
            if (!in_array($m->id, $existing, true)) {
                PenggunaanModul::create([
                    'master_sekolah_id' => $master->id,
                    'modul_id'          => $m->id,
                    'status'            => PenggunaanModul::ST_PROGRESS,
                    'mulai_tanggal'     => now(),
                    'is_official'       => false,
                    'created_by'        => Auth::id(),
                    'updated_by'        => Auth::id(),
                ]);
                $created++;
            }
        }

        if ($created > 0) $this->logAktivitas($master, "Lengkapi baris modul (buat $created)");

        return back()->with('ok', $created ? "Ditambah $created baris modul." : 'Semua baris 1-9 sudah lengkap.');
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
