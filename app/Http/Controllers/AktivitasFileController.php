<?php

namespace App\Http\Controllers;

use App\Models\AktivitasFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AktivitasFileController extends Controller
{
    /**
     * Izinkan preview image + PDF inline
     */
    public function preview(AktivitasFile $file)
    {
        // (opsional) authorize
        // $this->authorize('view', $file->aktivitas);

        if (!Storage::disk('public')->exists($file->path)) {
            abort(404);
        }

        $mime = (string) $file->mime;

        // Izinkan image ATAU PDF ditampilkan inline
        // Jika bukan keduanya, fallback ke download
        if (!str_starts_with($mime, 'image/') && $mime !== 'application/pdf') {
            return $this->download($file);
        }

        $absolute = Storage::disk('public')->path($file->path);
        
        return response()->file($absolute, [
            'Content-Type'        => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
            'Cache-Control'       => 'public, max-age=31536000',
        ]);
    }

    /**
     * Unduh file lampiran aktivitas (Force Download)
     */
    public function download(AktivitasFile $file)
    {
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
        // Optional: authorize delete
        // $this->authorize('delete', $file->aktivitas);

        // Hapus fisik file
        if (Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        // Hapus record database
        $file->delete();

        return back()->with('ok', 'Lampiran berhasil dihapus.');
    }
}