<?php

namespace App\Http\Controllers;

use App\Models\AktivitasFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class AktivitasFileController extends Controller
{
    /**
     * Unduh file lampiran aktivitas.
     */
    public function download(AktivitasFile $file)
    {
        // Optional: authorize
        // $this->authorize('view', $file->aktivitas);

        if (!Storage::disk('public')->exists($file->path)) {
            abort(404);
        }
        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    /**
     * Hapus file lampiran aktivitas.
     */
    public function destroy(AktivitasFile $file)
    {
        // Optional: authorize delete (admin/owner)
        // $this->authorize('delete', $file->aktivitas);

        // Hapus file dari storage
        Storage::disk('public')->delete($file->path);

        // Hapus record dari database
        $file->delete();

        return back()->with('ok', 'Lampiran berhasil dihapus.');
    }

    /**
     * Menampilkan pratinjau file (hanya gambar).
     */
    public function preview(AktivitasFile $file)
    {
        // Optional: authorize, contoh:
        // $this->authorize('view', $file->aktivitas);

        // Pastikan file ada
        if (!Storage::disk('public')->exists($file->path)) {
            abort(404);
        }

        // Kalau bukan gambar, tolak (biar <img> gak error)
        if (!str_starts_with((string) $file->mime, 'image/')) {
            abort(415, 'Hanya gambar yang bisa dipratinjau.');
        }

        $absolute = storage_path('app/public/'.$file->path);
        // Tampilkan inline
        return response()->file($absolute, [
            'Content-Type'        => $file->mime ?: 'image/*',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
            'Cache-Control'       => 'public, max-age=31536000',
        ]);
    }
}
