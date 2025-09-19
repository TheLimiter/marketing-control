<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            $t->index(['master_sekolah_id','modul_id']); // -> nama: penggunaan_modul_master_sekolah_id_modul_id_index
            $t->index('status');                          // -> nama: penggunaan_modul_status_index
            $t->index('akhir_tanggal');                   // -> nama: penggunaan_modul_akhir_tanggal_index
        });
    }

    public function down(): void {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            // drop dengan NAMA index (string) — paling aman lintas-driver
            $t->dropIndex('penggunaan_modul_master_sekolah_id_modul_id_index');
            $t->dropIndex('penggunaan_modul_status_index');
            $t->dropIndex('penggunaan_modul_akhir_tanggal_index');

            // Alternatif (juga valid): pakai array kolom
            // $t->dropIndex(['master_sekolah_id','modul_id']);
            // $t->dropIndex(['status']);
            // $t->dropIndex(['akhir_tanggal']);
        });
    }
};
