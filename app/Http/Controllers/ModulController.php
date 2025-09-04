<?php

namespace App\Http\Controllers;

use App\Models\Modul;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModulController extends Controller
{
    public function index(Request $r)
    {
        $items = Modul::when($r->filled('q'), fn($q)=>$q->where('nama','like','%'.$r->q.'%'))
                     ->orderBy('nama')->paginate(15)->withQueryString();
        return view('modul.index', compact('items'));
    }

    public function create() { return view('modul.create'); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'kode'          => ['required','string','max:100','unique:modul,kode'],
            'nama'          => ['required','string','max:150'],
            'kategori'      => ['nullable','string','max:50'],
            'versi'         => ['nullable','string','max:50'],
            'deskripsi'     => ['nullable','string'],
            'harga_default' => ['nullable','integer','min:0'],
            'aktif'         => ['nullable','boolean'],
        ]);

        $data['harga_default'] = $data['harga_default'] ?? 0;
        $data['aktif']         = $r->boolean('aktif');
        Modul::create($data);

        return redirect()->route('modul.index')->with('ok','Modul dibuat.');
    }

    public function edit(Modul $modul) { return view('modul.edit', compact('modul')); }

    public function update(Request $r, Modul $modul)
    {
        $data = $r->validate([
            'kode'          => ['required','string','max:100',Rule::unique('modul','kode')->ignore($modul->id)],
            'nama'          => ['required','string','max:150'],
            'kategori'      => ['nullable','string','max:50'],
            'versi'         => ['nullable','string','max:50'],
            'deskripsi'     => ['nullable','string'],
            'harga_default' => ['nullable','integer','min:0'],
            'aktif'         => ['nullable','boolean'],
        ]);

        $data['harga_default'] = $data['harga_default'] ?? 0;
        $data['aktif']         = $r->boolean('aktif');
        $modul->update($data);

        return back()->with('ok','Modul diperbarui.');
    }

    public function destroy(Modul $modul)
    {
        $modul->delete();
        return back()->with('ok','Modul dihapus.');
    }
}
