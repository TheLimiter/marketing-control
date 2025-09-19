<?php

namespace App\Http\Controllers;

use App\Models\BillingPaymentFile;
use Illuminate\Support\Facades\Storage;

class BillingPaymentFileController extends Controller
{
    // pratinjau (inline) — gambar/pdf
    public function preview(BillingPaymentFile $file)
    {
        // optional: $this->authorize('view', $file->tagihan);

        if (!Storage::disk('public')->exists($file->path)) {
            abort(404);
        }

        $absolute = Storage::disk('public')->path($file->path);
        return response()->file($absolute, [
            'Content-Type'        => $file->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
            'Cache-Control'       => 'public, max-age=31536000',
        ]);
    }

    // unduh (attachment)
    public function download(BillingPaymentFile $file)
    {
        if (!Storage::disk('public')->exists($file->path)) {
            abort(404);
        }
        return Storage::disk('public')->download($file->path, $file->original_name);
    }
}
