<?php
namespace App\Http\Controllers;
use App\Models\{Notifikasi, TagihanKlien};
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index()
    {
        $items = Notifikasi::with('tagihan.klien')->latest()->paginate(25);
        return view('notifikasi.index', compact('items'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'tagihan_id' => 'required|exists:tagihan_klien,id',
            'saluran'    => 'required|in:Email,WA,SMS',
            'isi_pesan'  => 'nullable|string'
        ]);
        $data['status'] = 'Antri';
        Notifikasi::create($data);
        // Integrasi pengiriman (queue) bisa ditambahkan nanti
        return back()->with('ok','Notifikasi dibuat (status: Antri)');
    }
}
