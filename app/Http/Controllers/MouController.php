<?php

namespace App\Http\Controllers;

use App\Models\MasterSekolah;
use App\Models\AktivitasProspek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MouController extends Controller
{
    /**
     * Form Upload/Update MOU
     */
    public function form(MasterSekolah $master)
    {
        return view('master.mou_form', compact('master'));
    }

    /**
     * Proses Simpan MOU ke tabel master_sekolah
     */
    public function save(Request $request, MasterSekolah $master)
    {
        $data = $request->validate([
            'mou'         => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:10240'], // Max 10MB
            'ttd_status'  => ['nullable','boolean'],
            'catatan'     => ['nullable','string','max:2000'],
            'next_action' => ['nullable','in:stay,billing'],
        ]);

        $changes = [];

        // 1. Handle File Upload
        if ($request->hasFile('mou')) {
            // Hapus file lama jika ada
            if ($master->mou_path && Storage::disk('public')->exists($master->mou_path)) {
                Storage::disk('public')->delete($master->mou_path);
            }

            // Simpan file baru
            $path = $request->file('mou')->store('mou_files', 'public');
            $master->mou_path = $path;
            
            $changes[] = 'Upload File MOU Baru';
        }

        // 2. Handle Status TTD
        // Input checkbox HTML mengirim value "1" atau null, kita cast ke boolean
        $inputTtd = $request->boolean('ttd_status');
        // Bandingkan dengan database (cast ke bool agar akurat)
        if ($inputTtd !== (bool) $master->ttd_status) {
            $master->ttd_status = $inputTtd;
            $changes[] = 'Status TTD: ' . ($inputTtd ? 'Sudah TTD' : 'Belum TTD');
        }

        // 3. Handle Catatan
        if (isset($data['catatan']) && $data['catatan'] !== $master->mou_catatan) {
            $master->mou_catatan = $data['catatan'];
            // Kita tidak perlu mencatat perubahan teks catatan di log aktivitas agar tidak spam
        }

        $master->save();

        // 4. Catat ke AktivitasProspek (Log History)
        if (!empty($changes)) {
            AktivitasProspek::create([
                'master_sekolah_id' => $master->id,
                'tanggal'           => now(),
                'jenis'             => 'mou_update', // Jenis khusus
                'hasil'             => implode(', ', $changes),
                'catatan'           => $data['catatan'] ?? null,
                'created_by'        => auth()->id(),
            ]);
        }

        // 5. Redirect
        if ($request->input('next_action') === 'billing') {
            return redirect()
                ->route('tagihan.create', ['master_sekolah_id' => $master->id])
                ->with('ok', 'MOU berhasil disimpan. Silakan buat tagihan.');
        }

        return redirect()
            ->route('master.index') // Sesuaikan route list sekolah Anda
            ->with('ok', 'Data MOU berhasil diperbarui.');
    }

    /**
     * Preview MOU (Solusi Anti-Forbidden)
     */
    public function preview(MasterSekolah $master)
    {
        // 1. Cek path di database
        if (!$master->mou_path) {
            abort(404, 'Belum ada file MOU yang diupload.');
        }

        // 2. Cek fisik file di storage
        if (!Storage::disk('public')->exists($master->mou_path)) {
            abort(404, 'File fisik MOU tidak ditemukan di server.');
        }

        // 3. Ambil Absolute Path dan Mime Type
        $path = Storage::disk('public')->path($master->mou_path);
        $mime = mime_content_type($path); // Deteksi otomatis tipe file

        // 4. Return file dengan header yang benar
        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="MOU-'.$master->nama_sekolah.'.pdf"',
            // Header ini kadang membantu browser menampilkan PDF alih-alih download
        ]);
    }

    /**
     * Download MOU
     */
    public function download(MasterSekolah $master)
    {
        if (!$master->mou_path || !Storage::disk('public')->exists($master->mou_path)) {
            abort(404, 'File MOU tidak ditemukan.');
        }

        // Bersihkan nama file agar aman saat didownload
        $filename = 'MOU_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $master->nama_sekolah) . '.' . pathinfo($master->mou_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($master->mou_path, $filename);
    }
}