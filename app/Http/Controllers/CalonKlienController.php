<?php

namespace App\Http\Controllers;

use App\Models\{CalonKlien, MasterSekolah}; // Tambahkan MasterSekolah
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CalonKlienController extends Controller
{
    /**
     * Daftar calon klien (+ pencarian sederhana).
     * View yang dipakai: resources/views/calon/index.blade.php
     * Variabel: $calon
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        // Menggunakan query dari MasterSekolah dengan status 'calon'
        $calon = MasterSekolah::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where('nama_sekolah', 'like', "%{$q}%")
                  ->orWhere('narahubung', 'like', "%{$q}%")
                  ->orWhere('no_hp', 'like', "%{$q}%");
            })
            ->where('status_klien', 'calon') // Filter berdasarkan status
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('calon.index', compact('calon', 'q'))
            ->with('items', $calon); // alias untuk kompatibilitas lama
    }

    /**
     * Form create (opsional; kalau pakai modal di index, ini bisa dibiarkan).
     */
    public function create()
    {
        return view('calon.create');
    }

    // ... (metode-metode lainnya)

    /**
     * Simpan calon klien baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_sekolah'  => ['required', 'string', 'max:255'],
            'alamat'        => ['nullable', 'string', 'max:255'],
            'no_hp'         => ['nullable', 'string', 'max:50'],
            'narahubung'    => ['nullable', 'string', 'max:120'],
            'jenjang'       => ['nullable', 'in:SD,SMP,SMA,SMK'],
            'sumber'        => ['nullable', 'string', 'max:120'],
            'catatan'       => ['nullable', 'string'],
        ]);

        // Simpan ke tabel master_sekolah
        $data['status_klien'] = 'calon';
        $calon = MasterSekolah::create($data);

        // Tambahkan logging
        $this->logUser('CREATE', 'Tambah Calon Klien dari Master #'.$calon->id);

        return redirect()->route('calon.index')->with('success', 'Calon klien ditambahkan.');
    }

    /**
     * Edit calon klien.
     */
    public function edit(CalonKlien $calon)
    {
        // Sesuaikan dengan MasterSekolah
        $masterSekolah = MasterSekolah::find($calon->id);
        return view('calon.edit', compact('masterSekolah'));
    }

    /**
     * Update calon klien.
     */
    public function update(Request $request, CalonKlien $calon)
    {
        $data = $request->validate([
            'nama_sekolah'  => ['required', 'string', 'max:255'],
            'alamat'        => ['nullable', 'string', 'max:255'],
            'no_hp'         => ['nullable', 'string', 'max:50'],
            'narahubung'    => ['nullable', 'string', 'max:120'],
            'jenjang'       => ['nullable', 'in:SD,SMP,SMA,SMK'],
            'sumber'        => ['nullable', 'string', 'max:120'],
            'catatan'       => ['nullable', 'string'],
        ]);

        // Simpan ke tabel master_sekolah
        $masterSekolah = MasterSekolah::find($calon->id);
        $masterSekolah->update($data);

        $this->logUser('UPDATE', 'Update Calon Klien dari Master #'.$masterSekolah->id);

        return redirect()->route('calon.index')->with('success', 'Data calon diperbarui.');
    }
}
