<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            if (!Schema::hasColumn('penggunaan_modul', 'mulai_tanggal')) {
                $t->date('mulai_tanggal')->nullable()->index();
            }
            if (!Schema::hasColumn('penggunaan_modul', 'akhir_tanggal')) {
                $t->date('akhir_tanggal')->nullable()->index();
            }
            if (!Schema::hasColumn('penggunaan_modul', 'last_used_at')) {
                $t->timestamp('last_used_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            foreach (['mulai_tanggal','akhir_tanggal','last_used_at'] as $c) {
                if (Schema::hasColumn('penggunaan_modul', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
