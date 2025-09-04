<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\MasterSekolah as MS;
use App\Models\AktivitasProspek;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Kirimkan statistik ke layout utama saja biar hemat
        View::composer('layouts.app', function ($view) {
            // Kalau model belum dimigrate, fail-safe nol semua:
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
                    'prospek'=>0,'negosiasi'=>0,'mou'=>0,'klien'=>0,'klienNoMou'=>0,'aktivitasNow'=>0,
                ];
            }

            $view->with('navStats', $navStats);
        });
    }
}
