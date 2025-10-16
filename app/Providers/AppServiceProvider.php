<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\MasterSekolah as MS;
use App\Models\AktivitasProspek;
use App\Observers\TagihanObserver;
use App\Observers\PenggunaanModulObserver;
use App\Observers\MouObserver;
use App\Observers\MasterSekolahObserver;
use App\Models\TagihanKlien;
use App\Models\PenggunaanModul;
use App\Models\Mou;
use App\Models\MasterSekolah;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // load ActivityHelper (format baru, pakai ActivityLogger)
        $file = base_path('app/Support/ActivityHelper.php');
        if (is_file($file)) {
            require_once $file;
        }

        // (opsional) load activity.php yg isi fungsi write_activity lama (tidak bentrok nama)
        $legacy = base_path('app/Support/activity.php');
        if (is_file($legacy)) {
            require_once $legacy;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (class_exists(TagihanKlien::class)) {
            TagihanKlien::observe(TagihanObserver::class);
        }
        if (class_exists(PenggunaanModul::class)) {
            PenggunaanModul::observe(PenggunaanModulObserver::class);
        }
        if (class_exists(Mou::class)) {
            Mou::observe(MouObserver::class);
        }
        if (class_exists(MasterSekolah::class)) {
            MasterSekolah::observe(MasterSekolahObserver::class);
        }

        // Kirimkan statistik ke layout utama (versi stage BARU)
View::composer('layouts.app', function ($view) {
    try {
        $navStats = [
            // stage baru
            'shb'   => MS::where('stage', MS::ST_SHB)->count(),      // sudah dihubungi
            'slth'  => MS::where('stage', MS::ST_SLTH)->count(),     // sudah dilatih
            'mou'   => MS::where('stage', MS::ST_MOU)->count(),      // MOU Aktif
            'tlmou' => MS::where('stage', MS::ST_TLMOU)->count(),    // Tindak lanjut MOU
            'tolak' => MS::where('stage', MS::ST_TOLAK)->count(),    // Ditolak

            // MOU aktif/tindak lanjut tanpa file
            'mouNoFile' => MS::whereIn('stage', [MS::ST_MOU, MS::ST_TLMOU])
                              ->whereNull('mou_path')
                              ->count(),

            // indikator aktivitas: gunakan created_at agar konsisten dgn tabel Aktivitas
            'aktivitasNow' => AktivitasProspek::whereDate('created_at', now()->toDateString())->count(),
        ];
    } catch (\Throwable $e) {
        $navStats = [
            'shb' => 0, 'slth' => 0, 'mou' => 0, 'tlmou' => 0, 'tolak' => 0,
            'mouNoFile' => 0, 'aktivitasNow' => 0,
        ];
    }

    $view->with('navStats', $navStats);
});

    }
}
