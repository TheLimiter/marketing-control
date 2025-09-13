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
    public function register(): void
    {
        $helpers = base_path('app/Support/activity_helpers.php');
        if (is_file($helpers)) {
            require_once $helpers;
        }
    }

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

        // Kirimkan statistik ke layout utama saja biar hemat
        View::composer('layouts.app', function ($view) {
            try {
                $navStats = [
                    'prospek'      => MS::where('stage', MS::ST_PROSPEK)->count(),
                    'negosiasi'    => MS::where('stage', MS::ST_NEGOSIASI)->count(),
                    'mou'          => MS::where('stage', MS::ST_MOU)->count(),
                    'klien'        => MS::where('stage', MS::ST_KLIEN)->count(),
                    'klienNoMou'   => MS::where('stage', MS::ST_KLIEN)->whereNull('mou_path')->count(),
                    'aktivitasNow' => AktivitasProspek::whereDate('tanggal', now()->toDateString())->count(),
                ];
            } catch (\Throwable $e) {
                $navStats = [
                    'prospek' => 0,
                    'negosiasi' => 0,
                    'mou' => 0,
                    'klien' => 0,
                    'klienNoMou' => 0,
                    'aktivitasNow' => 0,
                ];
            }

            $view->with('navStats', $navStats);
        });
    }
}
