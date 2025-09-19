<?php

namespace App\Http\Controllers;

use App\Models\TagihanKlien;
use App\Models\MasterSekolah;
use App\Models\AktivitasProspek;
use App\Models\PenggunaanModul;
use App\Models\Modul;
use App\Models\BillingPaymentFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\MasterSekolah as MS;
use App\Models\Notifikasi;

class TagihanController extends Controller
{
    // helper dasar filter yang sama untuk laporan & csv
    private function baseTagihanQuery(Request $r)
    {
        $q = \App\Models\TagihanKlien::query()
            ->with(['sekolah:id,nama_sekolah'])
            ->whereNull('deleted_at'); // soft delete aman

        if ($r->filled('master_sekolah_id')) {
            $q->where('master_sekolah_id', $r->integer('master_sekolah_id'));
        }

        if ($r->filled('status')) {
            // status: lunas / sebagian / draft
            $st = $r->status;
            if ($st === 'lunas') {
                $q->whereColumn('terbayar', '>=', 'total')->where('total', '>', 0);
            } elseif ($st === 'sebagian') {
                $q->whereColumn('terbayar', '<', 'total')->where('terbayar', '>', 0);
            } elseif ($st === 'draft') {
                $q->where(function ($w) {
                    $w->whereNull('total')->orWhere('total', '=', 0);
                });
            }
        }

        // range tanggal
        if ($r->filled('date_from')) $q->whereDate('tanggal_tagihan', '>=', $r->date('date_from'));
        if ($r->filled('date_to'))  $q->whereDate('tanggal_tagihan', '<=', $r->date('date_to'));

        // filter month (opsional)
        if ($r->filled('month')) {
            [$y,$m] = explode('-', $r->month);
            $q->whereYear('tanggal_tagihan', $y)->whereMonth('tanggal_tagihan', $m);
        }

        // search
        if ($r->filled('q')) {
            $s = trim($r->q);
            $q->where(function ($w) use ($s) {
                $w->where('nomor', 'like', "%$s%")
                    ->orWhere('keterangan', 'like', "%$s%");
            });
        }

        // due / overdue (laporan pakai due_only)
        if ($r->boolean('due_only')) {
            $q->whereColumn('terbayar', '<', 'total')
                ->whereDate('jatuh_tempo', '<=', now()->toDateString());
        }

        return $q;
    }

    public function laporan(Request $r)
    {
        $base = $this->baseTagihanQuery($r);

        // === RINGKASAN (TANPA orderBy!) ===
        $sum = (clone $base)
            ->selectRaw('COALESCE(SUM(total),0)      AS total')
            ->selectRaw('COALESCE(SUM(terbayar),0)       AS terbayar')
            ->selectRaw('COALESCE(SUM(GREATEST(total - terbayar,0)),0) AS sisa')
            ->selectRaw("SUM(CASE WHEN jatuh_tempo IS NOT NULL AND terbayar < total AND jatuh_tempo < CURRENT_DATE THEN 1 ELSE 0 END) AS overdue_count")
            ->first();

        $summary = [
            'total'           => (int) $sum->total,
            'terbayar'        => (int) $sum->terbayar,
            'sisa'            => (int) $sum->sisa,
            'overdue_count'   => (int) $sum->overdue_count,
            'collection_rate' => $sum->total > 0 ? round(($sum->terbayar / $sum->total) * 100, 1) : 0,
        ];

        // === LIST ITEM (boleh pakai orderBy) ===
        $items = (clone $base)
            ->orderByDesc('tanggal_tagihan')
            ->orderByDesc('id')
            ->paginate((int) $r->get('per', 25));

        // daftar klien buat dropdown
        $klien = \App\Models\MasterSekolah::orderBy('nama_sekolah')->get(['id','nama_sekolah']);

        return view('tagihan.laporan', compact('items','summary','klien'));
    }

    public function laporanExportCsv(Request $r)
    {
        $base = $this->baseTagihanQuery($r);

        // rows untuk CSV (boleh orderBy)
        $rows = (clone $base)
            ->orderByDesc('tanggal_tagihan')
            ->orderByDesc('id')
            ->get();

        return \Response::streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Sekolah','Nomor','Tgl Tagih','Jatuh Tempo','Total','Terbayar','Sisa','Status']);
            foreach ($rows as $t) {
                $sisa = max((int)$t->total - (int)$t->terbayar, 0);
                $status = ($t->total > 0 && $t->terbayar >= $t->total) ? 'lunas'
                                 : (($t->terbayar > 0) ? 'sebagian' : 'draft');

                fputcsv($out, [
                    optional($t->sekolah)->nama_sekolah,
                    $t->nomor,
                    optional($t->tanggal_tagihan)->format('Y-m-d'),
                    $t->jatuh_tempo,
                    (int)$t->total,
                    (int)$t->terbayar,
                    $sisa,
                    $status,
                ]);
            }
            fclose($out);
        }, 'laporan_tagihan_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Helper: ambil harga per siswa dari object Modul dengan fallback beragam kolom.
     */
    private function unitPrice(Modul $m): int
    {
        // urutkan prioritas harga_default dulu
        $candidates = ['harga_default','harga_per_siswa','harga','biaya','price','tarif'];
        foreach ($candidates as $c) {
            if (isset($m->{$c}) && is_numeric($m->{$c})) {
                return (int) $m->{$c};
            }
        }
        return 0;
    }

    public function index(Request $r)
    {
        $q = TagihanKlien::query()->with(['sekolah']);

        if ($r->filled('master_sekolah_id')) $q->where('master_sekolah_id', $r->integer('master_sekolah_id'));
        if ($r->filled('status')) $q->where('status', $r->string('status'));
        if ($r->filled('dari')) $q->whereDate('tanggal_tagihan', '>=', $r->date('dari'));
        if ($r->filled('sampai')) $q->whereDate('tanggal_tagihan', '<=', $r->date('sampai'));
        if ($r->filled('q')) {
            $s = trim($r->q);
            $q->where(function ($x) use ($s) {
                $x->where('nomor', 'like', "%{$s}%")
                    ->orWhere('catatan', 'like', "%{$s}%");
            });
        }

        // siapkan sekali
        $today = now()->toDateString();

        $onlyOverdue = $r->boolean('only_overdue');
        $onlyDue     = $r->boolean('only_due') && ! $onlyOverdue; // prioritas overdue

        if ($onlyDue) {
            $q->whereColumn('terbayar', '<', 'total')
                ->whereDate('jatuh_tempo', $today);
        }

        if ($onlyOverdue) {
            $q->whereColumn('terbayar', '<', 'total')
                ->whereDate('jatuh_tempo', '<', $today);
        }

        $items = (clone $q)->orderByDesc('tanggal_tagihan')->paginate(20)->withQueryString();

        $sum = (clone $q)->selectRaw('COALESCE(SUM(total),0) AS total, COALESCE(SUM(terbayar),0) AS terbayar')->first();
        $total     = (int) ($sum->total ?? 0);
        $terbayar  = (int) ($sum->terbayar ?? 0);
        $sisa      = $total - $terbayar;

        $today     = now()->toDateString();
        $due       = (clone $q)->whereColumn('terbayar', '<', 'total')->whereDate('jatuh_tempo', $today)->count();
        $overdue   = (clone $q)->whereColumn('terbayar', '<', 'total')->whereDate('jatuh_tempo', '<', $today)->count();
        $cr        = $total > 0 ? round(($terbayar / $total) * 100) : 0;

        $summary = compact('total', 'terbayar', 'sisa', 'due', 'overdue', 'cr');

        $sekolah = MasterSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']);
        $f = $r->all();

        return view('tagihan.index', compact('items', 'sekolah', 'summary', 'f'));
    }

    // --- bagian controller lainnya yang tidak diubah ---

    public function create(Request $r)
    {
        $prefillId = $r->integer('master_sekolah_id') ?: null;
        $sekolah   = MasterSekolah::orderBy('nama_sekolah')->get(['id','nama_sekolah','jumlah_siswa']);

        // ambil modul yang sedang dipakai oleh sekolah (jika sudah pilih sekolah)
        $assigned = collect();
        $defaultSiswa = null;

        if ($prefillId) {
            $defaultSiswa = optional(MasterSekolah::find($prefillId))->jumlah_siswa;
            $assigned = PenggunaanModul::with('modul')
                ->where('master_sekolah_id', $prefillId)
                ->get()
                ->pluck('modul')
                ->filter()
                ->map(fn($m)=>[
                    'id'   => $m->id,
                    'nama' => $m->nama,
                ])->values();
        }

        return view('tagihan.create', compact('sekolah','prefillId','assigned','defaultSiswa'));
    }

    /**
     * Endpoint preview perhitungan harga modul × jumlah siswa (JSON).
     * GET /tagihan/hitung?master_sekolah_id=..&siswa=..&modul_ids[]=1&modul_ids[]=2
     */
    public function hitung(Request $r)
    {
        $data = $r->validate([
            'master_sekolah_id'  => ['required','integer','exists:master_sekolah,id'],
            'siswa'              => ['nullable','integer','min:0'],
            'modul_ids'          => ['nullable','array'],
            'modul_ids.*'        => ['integer','exists:modul,id'],
        ]);

        $siswa = $data['siswa'] ?? optional(MasterSekolah::find($data['master_sekolah_id']))->jumlah_siswa ?? 0;

        // jika modul_ids kosong -> pakai modul yang terpasang pada sekolah
        $modulIds = $r->filled('modul_ids')
            ? array_values(array_unique($data['modul_ids']))
            : PenggunaanModul::where('master_sekolah_id', $data['master_sekolah_id'])->pluck('modul_id')->all();

        $modules = Modul::whereIn('id', $modulIds)->get();

        $rows = [];
        $total = 0;

        foreach ($modules as $m) {
            $u = $this->unitPrice($m); // harga per siswa
            $sub = $u * $siswa;
            $rows[] = [
                'id'       => $m->id,
                'nama'     => $m->nama,
                'harga'    => $u,
                'siswa'    => $siswa,
                'subtotal' => $sub,
            ];
            $total += $sub;
        }

        return response()->json([
            'ok'    => true,
            'siswa' => (int) $siswa,
            'items' => $rows,
            'total' => (int) $total,
        ]);
    }

    /**
     * Endpoint untuk mengambil modul terpasang per sekolah (JSON)
     */
    public function assignedModules(Request $r)
    {
        $data = $r->validate([
            'master_sekolah_id' => ['required','integer','exists:master_sekolah,id'],
        ]);

        $rows = \App\Models\PenggunaanModul::with(['modul:id,nama,harga_default'])
            ->where('master_sekolah_id', (int) $data['master_sekolah_id'])
            ->get()
            ->map(function ($pm) {
                if (!$pm->modul) return null;
                $m = $pm->modul;
                return [
                    'id'    => $m->id,
                    'nama'  => $m->nama,
                    'harga' => $this->unitPrice($m), // tetap pakai helper di controller ini
                ];
            })
            ->filter()
            ->values();

        return response()->json(['ok' => true, 'items' => $rows]);
    }


    // Tambah di dalam class TagihanController
    private function makeNomor(int $schoolId): string
    {
        // Format: INV-YYYYMMDD-SID-XXX
        $prefix = 'INV-'.now()->format('Ymd').'-'.$schoolId.'-';
        $last = \App\Models\TagihanKlien::where('nomor', 'like', $prefix.'%')
            ->orderByDesc('nomor')->value('nomor');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int)$m[1]) + 1;
        }
        return $prefix.str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $r)
    {
        // Validasi (jatuh_tempo sekarang WAJIB)
        $data = $r->validate([
            'master_sekolah_id' => ['required','integer','exists:master_sekolah,id'],
            'tanggal_tagihan'   => ['nullable','date'],
            'catatan'           => ['nullable','string'],
            'terbayar'          => ['nullable','numeric','min:0'],
            'buat_lagi'         => ['nullable','boolean'],
            'hitung_otomatis'   => ['nullable','boolean'],
            'siswa_count'       => ['nullable','integer','min:0'],
            'modul_ids'         => ['nullable','array'],
            'modul_ids.*'       => ['integer','exists:modul,id'],
            'total'             => ['nullable','integer','min:0'],
            'status'            => ['nullable','in:draft,sebagian,terkirim,lunas'],
            // WAJIB + pesan ramah
            'jatuh_tempo'       => ['required','date','after_or_equal:tanggal_tagihan'],
            'nomor'             => ['nullable','string','max:64'],
        ], [
            'jatuh_tempo.required'      => 'Jatuh tempo wajib diisi.',
            'jatuh_tempo.after_or_equal'=> 'Jatuh tempo tidak boleh sebelum tanggal tagihan.',
        ]);

        // Gate non-admin (tetap)
        $isAdmin = method_exists($r->user(), 'hasAnyRole') && $r->user()->hasAnyRole(['admin','superadmin']);
        if (!$isAdmin) {
            $sekolah = MS::findOrFail($data['master_sekolah_id']);
            if ((int)$sekolah->stage < MS::ST_MOU) {
                return back()->withErrors(['master_sekolah_id' => 'Stage sekolah belum MOU/KLIEN.'])->withInput();
            }
        }

        // Hitung total (tetap)
        if ($r->boolean('hitung_otomatis')) {
            $siswa = $data['siswa_count'] ?? optional(MasterSekolah::find($data['master_sekolah_id']))->jumlah_siswa ?? 0;
            $modulIds = $r->filled('modul_ids')
                ? array_values(array_unique($data['modul_ids']))
                : PenggunaanModul::where('master_sekolah_id', $data['master_sekolah_id'])->pluck('modul_id')->all();
            $modules = Modul::whereIn('id', $modulIds)->get();
            $total = 0;
            foreach ($modules as $m) $total += $this->unitPrice($m) * (int)$siswa;
        } else {
            if ($r->input('total') === null || $r->input('total') === '') {
                return back()->withErrors(['total' => 'Total tagihan wajib diisi.'])->withInput();
            }
            $total = (int) $r->input('total');
        }

        // --- Pastikan NOMOR SELALU ADA
        $nomor = trim((string) $r->input('nomor', ''));
        if ($nomor === '') {
            $nomor = $this->makeNomor((int)$data['master_sekolah_id']);
        }

        $payload = [
            'master_sekolah_id' => (int)$data['master_sekolah_id'],
            'nomor'             => $nomor,              // <-- pasti terisi
            'tanggal_tagihan'   => $r->input('tanggal_tagihan') ?: now()->toDateString(),
            'jatuh_tempo'       => $r->input('jatuh_tempo'),           // <-- sudah required
            'total'             => $total,
            'terbayar'          => (int)$r->input('terbayar', 0),
            'status'            => $r->input('status','draft'),
            'catatan'           => $r->input('catatan'),
        ];

        $t = TagihanKlien::create($payload);

        AktivitasProspek::create([
            'master_sekolah_id' => $t->master_sekolah_id,
            'tanggal'           => now(),
            'jenis'             => 'billing.create',
            'hasil'             => ($t->nomor ?: 'INV-'.$t->id).' / Rp '.number_format((float)$t->total, 0, ',', '.'),
            'catatan'           => $t->catatan,
            'created_by'        => auth()->id(),
        ]);

        if ($r->boolean('buat_lagi')) {
            return redirect()
                ->route('tagihan.create', ['master_sekolah_id' => $payload['master_sekolah_id'], 'buat_lagi' => 1])
                ->with('ok', 'Tagihan disimpan. Buat lagi untuk sekolah yang sama.');
        }

        return redirect()->route('tagihan.index')->with('ok', 'Tagihan berhasil dibuat.');
    }

    public function show(TagihanKlien $tagihan)
    {
        $tagihan->load('sekolah', 'notifikasi');
        return view('tagihan.show', compact('tagihan'));
    }

    public function edit(TagihanKlien $tagihan)
    {
        return view('tagihan.edit', [
            'tagihan' => $tagihan->load('sekolah'),
            'sekolah' => MasterSekolah::orderBy('nama_sekolah')->get(['id', 'nama_sekolah']),
        ]);
    }

    public function update(Request $r, TagihanKlien $tagihan)
    {
        $data = $r->validate([
            'master_sekolah_id' => ['required','integer','exists:master_sekolah,id'],
            'tanggal_tagihan'   => ['nullable','date'],
            'catatan'           => ['nullable','string'],
            'terbayar'          => ['nullable','numeric','min:0'],
            'jatuh_tempo'       => ['required','date','after_or_equal:tanggal_tagihan'], // <-- tambah required
        ], [
            'jatuh_tempo.required' => 'Jatuh tempo wajib diisi.',
            'jatuh_tempo.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal tagihan.',
        ]);

        $total = $r->input('total');
        if ($total === null || $total === '') {
            return back()->withErrors(['total' => 'Total tagihan wajib diisi.'])->withInput();
        }

        $status = $r->input('status', $tagihan->status);
        $nomor = $r->input('nomor', $tagihan->nomor);

        $payload = [
            'master_sekolah_id' => (int)$data['master_sekolah_id'],
            'nomor'             => $nomor,
            'tanggal_tagihan'   => $r->input('tanggal_tagihan') ?: $tagihan->tanggal_tagihan,
            'jatuh_tempo'       => $r->input('jatuh_tempo'),
            'total'             => (int)$total,
            'terbayar'          => (int)$r->input('terbayar', $tagihan->terbayar ?? 0),
            'status'            => $status,
            'catatan'           => $r->input('catatan'),
        ];

        $tagihan->update($payload);
        $this->refreshStatus($tagihan);

        return back()->with('ok', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(TagihanKlien $tagihan)
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize('delete', $tagihan);
        }
        $tagihan->delete();
        return back()->with('ok', 'Tagihan berhasil dihapus.');
    }

    // --- Pembayaran parsial/lunas ---
    public function bayarForm(TagihanKlien $tagihan)
    {
        return view('tagihan.bayar', compact('tagihan'));
    }

    public function bayarSimpan(Request $r, TagihanKlien $tagihan)
    {
        $data = $r->validate([
            'nominal'       => ['required', 'integer', 'min:1'],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode'        => ['nullable', 'string', 'max:50'],
            'catatan'       => ['nullable', 'string', 'max:255'],
            'bukti'         => ['nullable','file','max:5120','mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        DB::beginTransaction();

        try {
           $terbayarBaru = min((int)$tagihan->total, (int)$tagihan->terbayar + (int)$data['nominal']);

            $tagihan->update([
                'terbayar'   => $terbayarBaru,
                'status'     => $this->determineStatus($tagihan->total, $terbayarBaru),
                'updated_by' => auth()->id(),
            ]);

            $aktivitas = AktivitasProspek::create([
                'master_sekolah_id' => $tagihan->master_sekolah_id,
                'tanggal'           => $r->date('tanggal_bayar') ?: now(),
                'jenis'             => 'billing.payment',
                'hasil'             => "Pembayaran Rp " . number_format($data['nominal'], 0, ',', '.') . " untuk tagihan {$tagihan->nomor} (Metode: {$data['metode']})",
                'catatan'           => $data['catatan'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            if ($r->hasFile('bukti') && $r->file('bukti')->isValid()) {
                $f = $r->file('bukti');
                $path = $f->store('bukti_pembayaran', 'public');

                BillingPaymentFile::create([
                    'tagihan_id'    => $tagihan->id,
                    'aktivitas_id'  => $aktivitas->id,
                    'path'          => $path,
                    'original_name' => $f->getClientOriginalName(),
                    'mime'          => $f->getMimeType(),
                    'size'          => $f->getSize(),
                    'uploaded_by'   => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->route('tagihan.show', $tagihan)->with('ok', 'Pembayaran berhasil dicatat. Status tagihan diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('err', 'Terjadi kesalahan saat mencatat pembayaran: ' . $e->getMessage())->withInput();
        }
    }

    private function determineStatus(int $total, int $terbayar): string
    {
        if ($terbayar <= 0) {
            return 'draft';
        } elseif ($terbayar < $total) {
            return 'sebagian';
        } else {
            return 'lunas';
        }
    }

    private function refreshStatus(TagihanKlien $t): void
    {
        $t->status = $this->determineStatus($t->total, $t->terbayar);
        $t->save();
    }

    public function wa(TagihanKlien $tagihan)
    {
        if (method_exists($this, 'authorize')) {
            try { $this->authorize('view', $tagihan); } catch (\Throwable $e) {}
        }

        $sekolah = $tagihan->sekolah;

        $raw = $sekolah->narahubung_whatsapp
            ?? $sekolah->no_wa
            ?? $sekolah->wa
            ?? $sekolah->no_hp
            ?? $sekolah->hp
            ?? $sekolah->telepon
            ?? $sekolah->telp
            ?? null;

        if (! $raw) {
            return back()->with('err', 'Nomor WhatsApp tidak tersedia untuk sekolah ini.');
        }

        $phone = preg_replace('/\D+/', '', $raw ?? '');

        if (Str::startsWith($phone, '620')) {
            $phone = '62' . substr($phone, 3);
        }
        if (Str::startsWith($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if ($phone === '' || ! Str::startsWith($phone, '62')) {
            return back()->with('err', 'Nomor WA tidak valid. Gunakan format Indonesia (08xx / +62).');
        }

        $nomor      = $tagihan->nomor ?? ('INV-' . $tagihan->id);
        $nama       = $sekolah->nama_sekolah ?? 'Klien';
        $total      = number_format((float) ($tagihan->total ?? 0), 0, ',', '.');
        $jatuhTempo = optional($tagihan->jatuh_tempo)->format('d M Y');

        $pesan = "Halo *{$nama}*,\n\n"
            . "Terkait Tagihan *{$nomor}* dengan total *Rp {$total}*.\n"
            . ($jatuhTempo ? "Jatuh tempo: *{$jatuhTempo}*.\n" : "")
            . "Mohon konfirmasi pembayaran. Terima kasih.";

        $url = 'https://wa.me/' . $phone . '?text=' . urlencode($pesan);

        AktivitasProspek::create([
            'master_sekolah_id' => $tagihan->master_sekolah_id,
            'tanggal'           => now(),
            'jenis'             => 'billing.notify_sent',
            'hasil'             => 'WA: '.$phone,
            'catatan'           => 'Notifikasi tagihan dikirim via WA',
            'created_by'        => auth()->id(),
        ]);

        return redirect()->away($url);
    }

    public function notifikasiHMinus30(Request $r)
    {
        $days   = (int) ($r->get('hari', 30));
        $target = now()->addDays($days)->toDateString();

        $items = TagihanKlien::with('sekolah')
            ->whereDate('jatuh_tempo', $target)
            ->where('status', '!=', 'lunas')
            ->get();

        return view('tagihan.notif', compact('items', 'target', 'days'));
    }

    public function notifikasiJatuhTempo(Request $r)
    {
        // Panggil method yang sama dengan hari = 0
        $r->merge(['hari' => 0]);
        return $this->notifikasiHMinus30($r);
    }

    public function notifikasi(Request $r, TagihanKlien $tagihan)
    {
        $items = TagihanKlien::with('sekolah')
            ->where('id', $tagihan->id)->get();

        return view('tagihan.notif', [
            'items'   => $items,
            'tagihan' => $tagihan,
            'target'  => 'per_tagihan',
            'days'    => null,
        ]);
    }

    public function kirimNotifikasi(Request $r, TagihanKlien $tagihan)
    {
        $data = $r->validate([
            'saluran'   => 'required|in:Email,WA,SMS',
            'isi_pesan' => 'nullable|string',
        ]);

        Notifikasi::create([
            'tagihan_id' => $tagihan->id,
            'saluran'    => $data['saluran'],
            'isi_pesan'  => $data['isi_pesan'] ?? null,
            'status'     => 'Antri',
            'created_by' => auth()->id(),
        ]);

        return back()->with('ok', 'Notifikasi dibuat (status: Antri)');
    }
}
