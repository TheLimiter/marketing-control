<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
    Schema::table('penggunaan_modul', function (Blueprint $t) {
        $t->index(['master_sekolah_id','modul_id']);
        $t->index('status');
        $t->index('akhir_tanggal');
    });
}
public function down(): void {
    Schema::table('penggunaan_modul', function (Blueprint $t) {
        $t->dropIndex(['penggunaan_modul_master_sekolah_id_modul_id_index']);
        $t->dropIndex(['status']);
        $t->dropIndex(['akhir_tanggal']);
    });
}
};
