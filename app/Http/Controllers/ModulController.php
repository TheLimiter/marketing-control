<?php

namespace App\Http\Controllers;

use App\Models\Modul;
use App\Models\PenggunaanModul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function show(Modul $modul, Request $r)
    {
        // daftar sekolah yang menggunakan modul ini
        $q = PenggunaanModul::with(['master:id,nama_sekolah,stage', 'modul:id,nama'])
            ->where('modul_id', $modul->id);

        // filter opsional
        if ($r->filled('lisensi')) {
            if ($r->lisensi === 'official') $q->where('is_official', 1);
            if ($r->lisensi === 'trial')    $q->where('is_official', 0);
        }
        if ($r->filled('status')) {
            $q->where(function($x) use ($r) {
                $x->where(DB::raw('LOWER(status)'), strtolower($r->status))
                  ->orWhere('status', $r->status);
            });
        }
        if ($r->filled('q')) {
            $s = trim($r->q);
            $q->whereHas('master', fn($m)=>$m->where('nama_sekolah','like',"%{$s}%"));
        }

        $usage = (clone $q)->orderByDesc('last_used_at')->orderBy('mulai_tanggal')
            ->paginate(15)->withQueryString();

        // ringkasan
        $base = PenggunaanModul::where('modul_id', $modul->id);
        $total     = (clone $base)->count();
        $official  = (clone $base)->where('is_official',1)->count();
        $trial     = $total - $official;
        $aktif     = (clone $base)
            ->whereNull('akhir_tanggal')
            ->whereNotIn(DB::raw('LOWER(status)'), ['ended','selesai','done','completed'])
            ->count();
        $terakhir  = (clone $base)->max('updated_at');

        return view('modul.show', compact('modul','usage','total','official','trial','aktif','terakhir'));
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
