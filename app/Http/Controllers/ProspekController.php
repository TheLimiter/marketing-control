<?php

namespace App\Http\Controllers;

use App\Models\{Prospek, CalonKlien, Klien, LogPengguna, MasterSekolah};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProspekController extends Controller
{
    /**
     * Tampilkan daftar prospek dengan fitur pencarian dan pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        // Menggunakan query dari MasterSekolah dengan status 'prospek'
        $rows = MasterSekolah::query()
            ->when($q !== '', function ($w) use ($q) {
                // cari berdasarkan nama sekolah / narahubung / jenjang
                $w->where('nama_sekolah', 'like', "%{$q}%")
                  ->orWhere('narahubung', 'like', "%{$q}%")
                  ->orWhere('jenjang', 'like', "%{$q}%");
            })
            ->where('status_klien', 'prospek') // Filter berdasarkan status
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('prospek.index', [
            'items' => $rows,
            'q'     => $q,
        ]);
    }

    /**
     * Tampilkan form untuk membuat prospek baru.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Ambil semua calon klien untuk dropdown
        $calon = CalonKlien::orderBy('nama')->get();
        return view('prospek.create', compact('calon'));
    }

    /**
     * Simpan prospek baru ke database.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'calon_klien_id' => 'required|exists:calon_klien,id',
            'tanggal'        => 'required|date',
            'jenis'          => 'required|in:Undangan,Proposal,Kunjungan,Webinar,Call',
            'hasil'          => 'required|in:Follow Up,Positif,Negatif',
            'catatan'        => 'nullable',
        ]);

        $data['user_id'] = auth()->id();

        DB::transaction(function () use ($data) {
            $obj = Prospek::create($data);
            LogPengguna::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'CREATE',
                'keterangan' => 'Prospek #'.$obj->id
            ]);
        });

        return redirect()->route('prospek.index')->with('ok', 'Prospek dicatat');
    }

    /**
     * Konversi CALON -> PROSPEK.
     * Route name: calon.jadikan-prospek
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CalonKlien  $calon
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromCalon(Request $request, CalonKlien $calon)
    {
        // Cegah duplikat
        if (Prospek::where('calon_klien_id', $calon->id)->exists()) {
            return redirect()->route('prospek.index')->with('info', 'Calon ini sudah memiliki prospek.');
        }

        // Buat prospek baru (nilai standar konsisten dgn form Prospek)
        $data = [
            'calon_klien_id' => $calon->id,
            'tanggal'        => now()->toDateString(),
            'jenis'          => 'Call',
            'hasil'          => 'Follow Up',
            'catatan'        => 'Dibuat dari menu Calon Klien',
        ];

        if (Schema::hasColumn('prospek', 'created_by')) {
            $data['created_by'] = auth()->id();
        }

        $p = Prospek::create($data);

        // Tambahkan log Calon -> Prospek
        activity_log('calon.to_prospek', 'prospek', $p->id, null, [
            'calon_klien_id' => $calon->id,
            'jenis' => 'Call', 'hasil' => 'Follow Up'
        ]);

        // Langsung ke daftar prospek supaya terlihat
        return redirect()->route('prospek.index')->with('ok', 'Berhasil dijadikan prospek.');
    }

    // === MOU: Form ===
    public function mouForm(Prospek $prospek)
    {
        // Pastikan view menerima $prospek
        return view('prospek.mou', compact('prospek'));
    }

    // === MOU: Upload & TTD/Un-TTD ===
    public function mouUpload(Request $request, Prospek $prospek)
    {
        $data = $request->validate([
            'tanggal_mou' => ['nullable','date'],
            'mou_file'    => ['nullable','file','mimetypes:application/pdf','max:4096'],
            'action'      => ['nullable','in:save,ttd,unttd'],
        ]);

        // Catat data sebelum diubah
        $before = $prospek->only(['mou_file', 'mou_path', 'mou_at', 'tanggal_mou', 'ttd_status', 'ttd_at']);

        // 1) Simpan MOU (file + tanggal)
        if ($request->hasFile('mou_file')) {
            $path = $request->file('mou_file')->store('mou', 'public');
            // Standarkan: mou_file utama; mou_path fallback lama
            if (Schema::hasColumn('prospek','mou_file')) $prospek->mou_file = $path;
            if (Schema::hasColumn('prospek','mou_path')) $prospek->mou_path = $path;
        }

        if (isset($data['tanggal_mou'])) {
            if (Schema::hasColumn('prospek','mou_at'))         $prospek->mou_at = $data['tanggal_mou'];
            elseif (Schema::hasColumn('prospek','tanggal_mou')) $prospek->tanggal_mou = $data['tanggal_mou'];
        }

        // 2) Set TTD bila diminta di tombol
        if (($data['action'] ?? null) === 'ttd') {
            $prospek->ttd_status = (is_string($prospek->ttd_status) || $prospek->ttd_status === null) ? 'sudah' : 1;
            if (Schema::hasColumn('prospek','ttd_at')) $prospek->ttd_at = now();
        } elseif (($data['action'] ?? null) === 'unttd') {
            $prospek->ttd_status = (is_string($prospek->ttd_status) || $prospek->ttd_status === null) ? 'belum' : 0;
            if (Schema::hasColumn('prospek','ttd_at')) $prospek->ttd_at = null;
        }

        $prospek->save();

        // Catat data setelah diubah
        $after = $prospek->only(['mou_file', 'mou_path', 'mou_at', 'tanggal_mou', 'ttd_status', 'ttd_at']);

        // Log aktivitas MOU/TTD
        activity_log('prospek.mou.update', 'prospek', $prospek->id, $before, $after);

        return back()->with('ok', 'Data MOU/TTD prospek tersimpan.');
    }

    // === MOU: Download ===
    public function mouDownload(Prospek $prospek)
    {
        $file = null;
        if (Schema::hasColumn('prospek', 'mou_file') && $prospek->mou_file) {
            $file = $prospek->mou_file;
        } elseif (Schema::hasColumn('prospek', 'mou_path') && $prospek->mou_path) {
            $file = $prospek->mou_path;
        }

        if (!$file || !Storage::disk('public')->exists($file)) {
            return back()->with('error', 'File MOU belum ada.');
        }

        return Storage::disk('public')->download($file);
    }
}
