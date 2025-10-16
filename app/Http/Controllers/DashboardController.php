<?php

namespace App\Http\Controllers;

use App\Models\MasterSekolah as MS;
use App\Models\AktivitasProspek;
use App\Models\TagihanKlien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ringkasan pipeline (stage baru)
        $counts = [
            'calon'             => MS::where('stage', MS::ST_CALON)->count(),
            'sudah_dihubungi'   => MS::where('stage', MS::ST_SHB)->count(),
            'sudah_dilatih'     => MS::where('stage', MS::ST_SLTH)->count(),
            'mou_aktif'         => MS::where('stage', MS::ST_MOU)->count(),
            'tindak_lanjut_mou' => MS::where('stage', MS::ST_TLMOU)->count(),
            'ditolak'           => MS::where('stage', MS::ST_TOLAK)->count(),
        ];

        // MOU "tanpa file"
        $mouTanpaFile = MS::whereIn('stage', [MS::ST_MOU, MS::ST_TLMOU])
            ->whereNull('mou_path')
            ->count();

        // Minggu ini
        $start = now()->startOfWeek();
        $dihubungiThisWeek = AktivitasProspek::where('jenis', 'stage_change')->where('tanggal', '>=', $start)->where('hasil', 'like', '%→' . MS::ST_SHB)->count();
        $dilatihThisWeek = AktivitasProspek::where('jenis', 'stage_change')->where('tanggal', '>=', $start)->where('hasil', 'like', '%→' . MS::ST_SLTH)->count();
        $mouUpdatedThisWeek = AktivitasProspek::where('jenis', 'mou_update')->where('tanggal', '>=', $start)->count();

        // Statistik Tagihan
        $sum = TagihanKlien::query()
            ->selectRaw('COALESCE(SUM(total),0) AS total_amount, COALESCE(SUM(terbayar),0) AS total_paid, COUNT(*) as total_count, SUM(CASE WHEN jatuh_tempo IS NOT NULL AND terbayar < total AND jatuh_tempo < CURRENT_DATE THEN 1 ELSE 0 END) AS overdue_count')
            ->first();

        $billingStats = [
            'total'           => (int) $sum->total_count,
            'amount'          => (int) $sum->total_amount,
            'paid'            => (int) $sum->total_paid,
            'collection_rate' => $sum->total_amount > 0 ? round(($sum->total_paid / $sum->total_amount) * 100) : 0,
            'overdue'         => (int) $sum->overdue_count,
        ];

        $topOverdue = TagihanKlien::with('sekolah:id,nama_sekolah')->where('status', '!=', 'lunas')->whereNotNull('jatuh_tempo')->whereDate('jatuh_tempo', '<', now())->orderBy('jatuh_tempo', 'asc')->limit(3)->get();

        // --- BARU: Hitung ringkasan progress modul ---
        $stageCountsPerSchool = DB::table('penggunaan_modul')
            ->select('master_sekolah_id',
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(stage_modul,'')) = 'dilatih' THEN 1 ELSE 0 END) as cnt_dilatih"),
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(stage_modul,'')) = 'didampingi' THEN 1 ELSE 0 END) as cnt_didampingi"),
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(stage_modul,'')) = 'mandiri' THEN 1 ELSE 0 END) as cnt_mandiri")
            )
            ->groupBy('master_sekolah_id')
            ->get();

        $progressCounts = ['dilatih' => 0, 'didampingi' => 0, 'mandiri' => 0];
        foreach ($stageCountsPerSchool as $school) {
            $man = (int) $school->cnt_mandiri;
            $did = (int) $school->cnt_didampingi;
            $dil = (int) $school->cnt_dilatih;
            $total = $man + $did + $dil;
            if ($total === 0) continue;

            if ($man > 0 && $man >= $did && $man >= $dil) {
                $progressCounts['mandiri']++;
            } elseif ($did > 0 && $did >= $dil) {
                $progressCounts['didampingi']++;
            } else {
                $progressCounts['dilatih']++;
            }
        }

        // Aktivitas terbaru
        $recent = AktivitasProspek::with(['master:id,nama_sekolah', 'creator:id,name', 'files', 'paymentFiles'])
            ->select('id','master_sekolah_id','created_by','jenis','hasil','catatan','tanggal')
            ->latest('tanggal')
            ->limit(10)
            ->get();

        // (Transformasi tidak diubah)
        $recent->transform(function ($r) {
            if ($r->jenis === 'stage_change') {
                if (preg_match('/(\d+)\s*(?:->|→|to|-|—|>)\s*(\d+)/i', (string) $r->hasil, $m)) {
                    $r->display_jenis = MS::stageLabel((int)$m[1]) . ' → ' . MS::stageLabel((int)$m[2]);
                }
                $r->display_hasil = 'Perubahan Tahap';
            }
            return $r;
        });

        return view('dashboard', compact(
            'counts', 'mouTanpaFile', 'dihubungiThisWeek', 'dilatihThisWeek',
            'mouUpdatedThisWeek', 'billingStats', 'topOverdue', 'recent',
            'progressCounts' // <-- Kirim data baru ke view
        ));
    }
}
