<?php
namespace App\Http\Controllers;

use App\Models\{Klien, TagihanKlien, LogPengguna, Prospek, CalonKlien};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KlienController extends Controller
{
    public function index(){ $items = Klien::latest()->paginate(20); return view('klien.index', compact('items')); }

    public function create()
    {
        $klien = new \App\Models\Klien();
        return view('klien.create', compact('klien'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama'        => ['required','string','max:255'],
            'tanggal_mou' => ['nullable','date'],
            'mou_file'    => ['nullable','file','mimetypes:application/pdf','max:4096'],
            'status_ttd'  => ['nullable','in:sudah,belum'],
        ]);

        if ($r->hasFile('mou_file')) {
            $data['mou_file'] = $r->file('mou_file')->store('mou','public');
        }

        $obj = Klien::create($data);
        LogPengguna::create(['user_id'=>auth()->id(),'aktivitas'=>'CREATE','keterangan'=>'Klien #'.$obj->id]);

        return redirect()->route('klien.index')->with('ok','Klien ditambahkan.');
    }

    public function markTtd(Klien $klien){ $klien->update(['status_ttd'=>'sudah']); return back()->with('ok','MOU ditandatangani'); }

    /**
     * Konversi Prospek menjadi Klien.
     * Metode ini mengambil data dari prospek dan membuat entri baru di tabel klien.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Prospek  $prospek
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromProspek(Request $request, Prospek $prospek)
    {
        // Temukan calon terkait
        $calon = method_exists($prospek, 'calon') ? $prospek->calon()->first() : \App\Models\CalonKlien::find($prospek->calon_klien_id);
        if (!$calon) {
            return back()->with('error', 'Data calon tidak ditemukan.');
        }

        // Ambil nilai MOU/TTD dari prospek
        $mouFile   = $prospek->mou_file ?? $prospek->mou_path ?? null;
        $tglMou    = $prospek->mou_at ?? $prospek->tanggal_mou ?? null;
        $statusTtd = $prospek->ttd_status ?? null; // 'sudah'/'belum' atau 1/0

        // Kunci unik klien (pilih sesuai skema)
        $key = \Schema::hasColumn('klien','calon_klien_id')
            ? ['calon_klien_id' => $calon->id]
            : ['nama' => $calon->nama];

        // Ambil/buat klien
        $klien = \App\Models\Klien::firstOrCreate($key, [
            'nama'       => $calon->nama,
            'alamat'     => $calon->alamat ?? null,
            'no_hp'      => $calon->no_hp ?? null,
            'narahubung' => $calon->narahubung ?? null,
            'jenjang'    => $calon->jenjang ?? null,
        ]);

        // --- Pastikan nilai MOU/TTD DISALIN SELALU (baru atau sudah ada) ---
        if (\Schema::hasColumn('klien','tanggal_mou')) $klien->tanggal_mou = $tglMou;
        if (\Schema::hasColumn('klien','mou_file'))    $klien->mou_file    = $mouFile;
        if (\Schema::hasColumn('klien','mou_path') && !$klien->mou_file) {
            // fallback jika skema lama pakai mou_path
            $klien->mou_path = $mouFile;
        }
        if (\Schema::hasColumn('klien','status_ttd') && $statusTtd !== null) {
            // normalisasi enum/boolean
            $klien->status_ttd = (in_array($statusTtd, ['sudah','belum'], true))
                ? $statusTtd
                : ((int)$statusTtd === 1 ? 'sudah' : 'belum');
        }
        if (\Schema::hasColumn('klien','ttd_at')) {
            $klien->ttd_at = (($klien->status_ttd === 'sudah') ? now() : null);
        }

        $klien->save();

        // Tandai prospek positif (opsional)
        $prospek->hasil = 'Positif';
        $prospek->save();

        // Log aktivitas
        activity_log('prospek.to_klien', 'klien', $klien->id, null, [
            'calon_klien_id' => $calon->id ?? null,
            'tanggal_mou'    => $klien->tanggal_mou ?? null,
            'status_ttd'     => $klien->status_ttd ?? null,
        ]);

        // Sesuai alurmu: kembali ke daftar klien
        return redirect()->route('klien.index')->with('info', 'Prospek dijadikan Klien.');
    }

    public function edit(Klien $klien)
    {
        // Form untuk lengkapi MOU/TTD (sesuai alur Prospek -> Klien)
        return view('klien.edit', compact('klien'));
    }

    public function update(Request $request, Klien $klien)
    {
        // Catat data sebelum diubah
        $before = $klien->only(['tanggal_mou','mou_file','status_ttd','ttd_at']);

        $data = $request->validate([
            'tanggal_mou' => ['nullable','date'],
            'mou_file'    => ['nullable','file','mimetypes:application/pdf','max:4096'],
            'status_ttd'  => ['nullable','in:sudah,belum'], // pakai enum string
        ]);

        // Simpan file MOU (ke disk 'public')
        if ($request->hasFile('mou_file')) {
            $path = $request->file('mou_file')->store('mou', 'public');

            // Kolom utama: mou_file (fallback mou_path jika ada skema lama)
            if (Schema::hasColumn('klien', 'mou_file'))   $klien->mou_file = $path;
            if (Schema::hasColumn('klien', 'mou_path'))   $klien->mou_path = $path;
        }

        // Tanggal MOU
        if (isset($data['tanggal_mou'])) {
            if (Schema::hasColumn('klien','tanggal_mou')) $klien->tanggal_mou = $data['tanggal_mou'];
        }

        // Status TTD
        if (isset($data['status_ttd']) && Schema::hasColumn('klien','status_ttd')) {
            $klien->status_ttd = $data['status_ttd'];                 // 'sudah' / 'belum'
            if (Schema::hasColumn('klien','ttd_at')) {
                $klien->ttd_at = $data['status_ttd'] === 'sudah' ? now() : null;
            }
        }

        $klien->save();

        // Catat data setelah diubah
        $after = $klien->only(['tanggal_mou','mou_file','status_ttd','ttd_at']);

        // Log aktivitas
        activity_log('klien.update', 'klien', $klien->id, $before, $after);

        return back()->with('ok', 'Data MOU/TTD klien berhasil disimpan.');
    }
}
