<?php

namespace App\Http\Controllers;

use App\Models\MasterSekolah as MS;
use App\Models\AktivitasProspek;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ringkasan pipeline
        $counts = [
            'calon'     => MS::where('stage', MS::ST_CALON)->count(),
            'prospek'   => MS::where('stage', MS::ST_PROSPEK)->count(),
            'negosiasi' => MS::where('stage', MS::ST_NEGOSIASI)->count(),
            'mou'       => MS::where('stage', MS::ST_MOU)->count(),
            'klien'     => MS::where('stage', MS::ST_KLIEN)->count(),
        ];

        $klienTanpaMou = MS::where('stage', MS::ST_KLIEN)->whereNull('mou_path')->count();

        // Minggu ini (mulai Senin / startOfWeek app timezone)
        $start = now()->startOfWeek();

        // “Masuk Prospek” minggu ini → deteksi log stage_change ...→2 (ST_PROSPEK)
        $prospekThisWeek = AktivitasProspek::where('jenis', 'stage_change')
            ->where('hasil', 'like', '%→' . MS::ST_PROSPEK)
            ->where('tanggal', '>=', $start)
            ->count();

        // Update MOU minggu ini
        $mouUpdatedThisWeek = AktivitasProspek::where('jenis', 'mou_update')
            ->where('tanggal', '>=', $start)
            ->count();

        // Aktivitas terbaru (tampilkan nama sekolah)
        $recent = AktivitasProspek::with(['master:id,nama_sekolah'])
            ->select('id','master_sekolah_id','jenis','hasil','catatan','tanggal')
            ->latest('tanggal')
            ->limit(10)
            ->get();

        // format tampilan untuk kolom "Jenis" & "Hasil"
        $recent->transform(function ($r) {
            if ($r->jenis === 'stage_change') {
                // hasil disimpan spt "1→2", ambil angkanya
                $from = $to = null;
                if (preg_match('/(\d+)\D+(\d+)/', (string) $r->hasil, $m)) {
                    $from = (int) $m[1];
                    $to   = (int) $m[2];
                }
                $r->display_jenis = MS::stageLabel($from) . ' → ' . MS::stageLabel($to); // contoh: Prospek → Klien
                $r->display_hasil = 'Pindah Tahap';
                $r->badge_class   = 'info';
            } elseif ($r->jenis === 'mou_update') {
                $r->display_jenis = 'MOU / TTD';
                // contoh hasil: "upload MOU, TTD ON"
                $r->display_hasil = (string) $r->hasil;
                $r->badge_class   = 'primary';
            } else {
                $r->display_jenis = ucwords(str_replace('_',' ', (string) $r->jenis));
                $r->display_hasil = (string) $r->hasil;
                $r->badge_class   = 'secondary';
            }
            return $r;
        });

        return view('dashboard', compact(
            'counts','klienTanpaMou','prospekThisWeek','mouUpdatedThisWeek','recent'
        ));
    }
}
