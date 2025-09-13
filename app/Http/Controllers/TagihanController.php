<?php
namespace App\Http\Controllers;

use App\Models\{TagihanKlien, MasterSekolah, Notifikasi};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use App\Http\Requests\Tagihan\StoreTagihanRequest;
use App\Http\Requests\Tagihan\UpdateTagihanRequest;
use App\Models\MasterSekolah as MS;

class TagihanController extends Controller
{
    /**
     * Build query + ringkasan.
     *
     * @param Request $r
     * @param bool $forCsv
     * @return array
     */
    private function buildLaporanQuery(Request $r, bool $forCsv = false)
    {
        $klien = MasterSekolah::orderBy('nama_sekolah')->get();

        $q = TagihanKlien::with('sekolah')->orderBy('tanggal_tagihan','desc');

        // FILTER: klien
        if ($r->filled('master_sekolah_id')) {
            $q->where('master_sekolah_id', $r->integer('master_sekolah_id'));
        }

        // FILTER: status
        if ($r->filled('status')) {
            $q->where('status', $r->string('status'));
        }

        // FILTER: bulan (YYYY-MM)
        if ($r->filled('month')) {
            try {
                $start = Carbon::parse($r->month.'-01')->startOfMonth();
                $end   = (clone $start)->endOfMonth();
                $q->whereBetween('tanggal_tagihan', [$start, $end]);
            } catch (\Throwable $e) { /* ignore */ }
        }

        // FILTER: rentang tanggal
        if ($r->filled('date_from')) {
            $q->whereDate('tanggal_tagihan', '>=', $r->date('date_from'));
        }
        if ($r->filled('date_to')) {
            $q->whereDate('tanggal_tagihan', '<=', $r->date('date_to'));
        }

        // FILTER: due only
        if ((bool)$r->boolean('due_only')) {
            $today = now()->toDateString();
            $q->whereColumn('terbayar', '<', 'total')
              ->whereDate('jatuh_tempo', '<=', $today);
        }

        // FILTER: search
        if ($r->filled('q')) {
            $s = trim($r->q);
            $q->where(function($x) use ($s) {
                $x->where('nomor','like',"%{$s}%")
                  ->orWhere('catatan','like',"%{$s}%");
            });
        }

        // Ringkasan
        $sumQ = (clone $q)->selectRaw('COALESCE(SUM(total),0) as total, COALESCE(SUM(terbayar),0) as terbayar')->first();
        $summary = [
            'total'      => (int)$sumQ->total,
            'terbayar'   => (int)$sumQ->terbayar,
            'sisa'       => (int)$sumQ->total - (int)$sumQ->terbayar,
        ];

        // Overdue count untuk ringkasan
        $overdueCount = (clone $q)
            ->whereDate('jatuh_tempo', '<', now())
            ->whereColumn('terbayar', '<', 'total')
            ->count();
        $summary['overdue_count'] = $overdueCount;
        $summary['collection_rate'] = $summary['total'] > 0 ? round(($summary['terbayar'] / $summary['total']) * 100) : 0;

        if ($forCsv) {
            return [$q, $summary, $klien];
        }

        $items = $q->paginate(50)->withQueryString();
        return [$items, $summary, $klien];
    }

    public function laporan(Request $r)
    {
        // Ambil items (paginator), summary, dan daftar klien untuk filter
        [$items, $summary, $klien] = $this->buildLaporanQuery($r, false);
        return view('tagihan.laporan', compact('items','summary','klien'));
    }

    public function laporanExportCsv(Request $r)
    {
        // Ambil query builder (bukan paginator) agar bisa stream data besar
        [$q, $summary, $klien] = $this->buildLaporanQuery($r, true);

        $filename = 'laporan-tagihan-'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type'      => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');
            // Tulis BOM UTF-8 agar Excel nyaman
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom
            fputcsv($out, ['Klien','Nomor','Tanggal','Jatuh Tempo','Total','Terbayar','Sisa','Status','Catatan']);

            $q->orderByDesc('tanggal_tagihan')->chunk(500, function($rows) use ($out) {
                foreach ($rows as $t) {
                    $sisa = max((int)$t->total - (int)$t->terbayar, 0);
                    fputcsv($out, [
                        $t->sekolah->nama_sekolah ?? '-',
                        $t->nomor,
                        $t->tanggal_tagihan,
                        $t->jatuh_tempo,
                        (int)$t->total,
                        (int)$t->terbayar,
                        $sisa,
                        $t->status,
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    public function index(Request $r)
    {
        $q = TagihanKlien::query()->with(['sekolah']);

        // === Filter, mengikuti nama input di Blade ===
        if ($r->filled('master_sekolah_id')) {
            $q->where('master_sekolah_id', $r->integer('master_sekolah_id'));
        }
        if ($r->filled('status')) {
            $q->where('status', $r->string('status'));
        }
        if ($r->filled('dari')) {
            $q->whereDate('tanggal_tagihan', '>=', $r->date('dari'));
        }
        if ($r->filled('sampai')) {
            $q->whereDate('tanggal_tagihan', '<=', $r->date('sampai'));
        }
        if ($r->filled('q')) {
            $s = trim($r->q);
            $q->where(function ($x) use ($s) {
                $x->where('nomor', 'like', "%{$s}%")
                  ->orWhere('catatan', 'like', "%{$s}%");
            });
        }
        // Due (hari ini) & Overdue
        if ($r->boolean('only_due')) {
            $today = now()->toDateString();
            $q->whereColumn('terbayar','<','total')
              ->whereDate('jatuh_tempo', $today);
        }
        if ($r->boolean('only_overdue')) {
            $q->whereColumn('terbayar','<','total')
              ->whereDate('jatuh_tempo','<',now()->toDateString());
        }

        // === Data tabel
        $items = (clone $q)->orderByDesc('tanggal_tagihan')
            ->paginate(20)->withQueryString();

        // === Ringkasan
        $sum = (clone $q)
            ->selectRaw('COALESCE(SUM(total),0) AS total, COALESCE(SUM(terbayar),0) AS terbayar')
            ->first();
        $total    = (int)($sum->total ?? 0);
        $terbayar = (int)($sum->terbayar ?? 0);
        $sisa     = $total - $terbayar;

        $today = now()->toDateString();
        $due = (clone $q)->whereColumn('terbayar','<','total')->whereDate('jatuh_tempo', $today)->count();
        $overdue = (clone $q)->whereColumn('terbayar','<','total')->whereDate('jatuh_tempo','<',$today)->count();
        $cr = $total > 0 ? round(($terbayar / $total) * 100) : 0;

        $summary = compact('total','terbayar','sisa','due','overdue','cr');

        // Dropdown klien + state filter buat isi ulang form
        $sekolah = MasterSekolah::orderBy('nama_sekolah')->get(['id','nama_sekolah']);
        $f = $r->all();

        return view('tagihan.index', compact('items','sekolah','summary','f'));
    }

    public function create()
    {
        return view('tagihan.create', [
            'sekolah' => MasterSekolah::orderBy('nama_sekolah')->get(['id','nama_sekolah']),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'master_sekolah_id'     => ['required','integer','exists:master_sekolah,id'],
            'jumlah_tagihan'        => ['required','numeric','min:0'],
            'tanggal_tagihan'       => ['nullable','date'],
            'tanggal_jatuh_tempo'   => ['nullable','date','after_or_equal:tanggal_tagihan'],
            'status_pembayaran'     => ['nullable','in:belum,lunas,parsial'],
            // kolom lain kalau ada…
        ]);

        // BYPASS untuk admin (role-based)
        $isAdmin = method_exists($r->user(), 'hasAnyRole')
            ? $r->user()->hasAnyRole(['admin','superadmin'])
            : false;

        if (! $isAdmin) {
            $sekolah = MS::findOrFail($data['master_sekolah_id']);
            if ((int) $sekolah->stage < MS::ST_MOU) {
                return back()
                    ->withErrors(['master_sekolah_id' => 'Stage sekolah belum MOU/KLIEN. User tidak diizinkan membuat tagihan.'])
                    ->withInput();
            }
        }

        // create seperti biasa
        $t = TagihanKlien::create($data);
        return redirect()->route('tagihan.index')->with('ok','Tagihan berhasil dibuat.');
    }

    public function show(TagihanKlien $tagihan)
    {
        $tagihan->load('sekolah','notifikasi');
        return view('tagihan.show', compact('tagihan'));
    }

    public function edit(TagihanKlien $tagihan)
    {
        return view('tagihan.edit', [
            'tagihan' => $tagihan->load('sekolah'),
            'sekolah' => MasterSekolah::orderBy('nama_sekolah')->get(['id','nama_sekolah']),
        ]);
    }

    public function update(Request $r, TagihanKlien $tagihan)
    {
        $data = $r->validate([
            'master_sekolah_id'     => ['required','integer','exists:master_sekolah,id'],
            'jumlah_tagihan'        => ['required','numeric','min:0'],
            'tanggal_tagihan'       => ['nullable','date'],
            'tanggal_jatuh_tempo'   => ['nullable','date','after_or_equal:tanggal_tagihan'],
            'status_pembayaran'     => ['nullable','in:belum,lunas,parsial'],
            // kolom lain…
        ]);

        $isAdmin = method_exists($r->user(), 'hasAnyRole')
            ? $r->user()->hasAnyRole(['admin','superadmin'])
            : false;

        if (! $isAdmin) {
            $sekolah = MS::findOrFail($data['master_sekolah_id']);
            if ((int) $sekolah->stage < MS::ST_MOU) {
                return back()
                    ->withErrors(['master_sekolah_id' => 'Stage sekolah belum MOU/KLIEN. User tidak diizinkan mengubah tagihan ke sekolah ini.'])
                    ->withInput();
            }
        }

        $tagihan->update($data);
        return back()->with('ok','Tagihan berhasil diperbarui.');
    }

    public function destroy(TagihanKlien $tagihan)
    {
        $this->authorize('delete', $tagihan);
        $tagihan->delete(); // soft delete OK
        return back()->with('ok', 'Tagihan dipindahkan ke riwayat.');
    }

    // --- Pembayaran parsial/lunas ---
    public function bayarForm(\App\Models\TagihanKlien $tagihan) {
        return view('tagihan.bayar', compact('tagihan'));
    }

    public function bayarSimpan(Request $r, TagihanKlien $tagihan)
    {
        $data = $r->validate([
            'nominal'      => ['required','integer','min:1'],
            'tanggal_bayar' => ['nullable','date'],
            'metode'       => ['nullable','string','max:50'],
            'catatan'      => ['nullable','string','max:255'],
        ]);

        $terbayarBaru = min((int)$tagihan->total, (int)$tagihan->terbayar + (int)$data['nominal']);
        $tagihan->forceFill([
            'terbayar' => $terbayarBaru,
            // status akan disinkron otomatis di model (lihat Bagian 3)
            'updated_by' => auth()->id(),
        ])->save();

        return redirect()->route('tagihan.show',$tagihan)->with('ok','Pembayaran dicatat.');
    }

    private function refreshStatus(TagihanKlien $t): void
    {
        if ($t->terbayar <= 0)      $t->status = 'draft';
        elseif ($t->terbayar < $t->total)   $t->status = 'sebagian';
        else                                $t->status = 'lunas';
        $t->save();
    }

    public function wa(TagihanKlien $tagihan)
    {
        // otorisasi minimal
        if (method_exists($this, 'authorize')) {
            try { $this->authorize('view', $tagihan); } catch (\Throwable $e) {}
        }

        $sekolah = $tagihan->sekolah; // pastikan relasi 'sekolah' ada di model TagihanKlien

        // cari nomor WA dari beberapa kemungkinan kolom
        $raw = $sekolah->narahubung_whatsapp
            ?? $sekolah->no_wa
            ?? $sekolah->wa
            ?? $sekolah->no_hp
            ?? $sekolah->hp
            ?? $sekolah->telepon
            ?? $sekolah->telp
            ?? null;

        if (!$raw) {
            return back()->with('err','Nomor WhatsApp tidak tersedia untuk sekolah ini.');
        }

        // normalisasi nomor → digit-only
        $phone = preg_replace('/\D+/', '', $raw ?? '');

        // buang awalan 62 ganda seperti 6208...
        if (Str::startsWith($phone, '620')) {
            $phone = '62' . substr($phone, 3); // 6208... -> 628...
        }

        // ubah 0xxxx -> 62xxxx
        if (Str::startsWith($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if ($phone === '' || !Str::startsWith($phone, '62')) {
            return back()->with('err','Nomor WA tidak valid. Gunakan format Indonesia (08xx / +62).');
        }

        // siapkan pesan
        $nomor  = $tagihan->nomor_tagihan ?? ('INV-' . $tagihan->id);
        $nama   = $sekolah->nama_sekolah ?? 'Klien';
        $total  = number_format((float)($tagihan->jumlah_tagihan ?? 0), 0, ',', '.');
        $jatuhTempo = optional($tagihan->tanggal_jatuh_tempo ?? $tagihan->jatuh_tempo ?? null)->format('d M Y');

        $pesan = "Halo *{$nama}*,\n\n".
                 "Terkait Tagihan *{$nomor}* dengan total *Rp {$total}*.\n".
                 ($jatuhTempo ? "Jatuh tempo: *{$jatuhTempo}*.\n" : "").
                 "Mohon konfirmasi pembayaran. Terima kasih.";

        $url = 'https://wa.me/' . $phone . '?text=' . urlencode($pesan);

        log_activity(
          'tagihan.notify_sent',
          $tagihan,
          [], // before
          ['channel' => 'WA', 'to' => $phone, 'id' => $tagihan->id],
          $tagihan->master_sekolah_id,
          'Notifikasi tagihan dikirim'
        );

        return redirect()->away($url);
    }

    // Notifikasi H-<n> jatuh tempo (default 30 hari)
    public function notifikasiHMinus30(\Illuminate\Http\Request $r)
    {
        $days   = (int)($r->get('hari', 30)); // boleh override ?hari=7, dll
        $target = now()->addDays($days)->toDateString();

        $items = \App\Models\TagihanKlien::with('sekolah')
            ->whereDate('jatuh_tempo', $target)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'lunas');   // hanya yang belum lunas
            })
            ->get();

        return view('tagihan.notif', compact('items','target','days'));
    }

    // Alias agar kompatibel dengan nama route lain seperti /tagihan/notifikasi/jatuh-tempo
    public function notifikasiJatuhTempo(\Illuminate\Http\Request $r)
    {
        return $this->notifikasiHMinus30($r);
    }

    // Notifikasi per tagihan
    public function notifikasi(Request $r, TagihanKlien $tagihan)
    {
        // tampilkan halaman untuk kirim notifikasi 1 tagihan
        // atau listing notifikasi yang relevan (opsional)
        $items = TagihanKlien::with('sekolah')
            ->where('id', $tagihan->id)->get();

        return view('tagihan.notif', [
            'items'    => $items,
            'tagihan'  => $tagihan,
            'target'   => 'per_tagihan',
            'days'     => null,
        ]);
    }

    public function kirimNotifikasi(Request $r, TagihanKlien $tagihan)
    {
        $data = $r->validate([
            'saluran'    => 'required|in:Email,WA,SMS',
            'isi_pesan' => 'nullable|string',
        ]);

        \App\Models\Notifikasi::create([
            'tagihan_id' => $tagihan->id,
            'saluran'    => $data['saluran'],
            'isi_pesan'  => $data['isi_pesan'] ?? null,
            'status'     => 'Antri',
            'created_by' => auth()->id(),
        ]);

        return back()->with('ok','Notifikasi dibuat (status: Antri)');
    }
}
