<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('modul', function (Blueprint $t) {
            // Tambah kolom harga_default bila belum ada
            if (!Schema::hasColumn('modul','harga_default')) {
                // simpan rupiah sebagai integer (contoh: 2900000)
                $t->unsignedBigInteger('harga_default')->default(0);
            }
            // (opsional) kalau kamu juga pakai kategori & versi, tambahkan juga:
            if (!Schema::hasColumn('modul','kategori')) {
                $t->string('kategori', 50)->nullable();
            }
            if (!Schema::hasColumn('modul','versi')) {
                $t->string('versi', 50)->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('modul', function (Blueprint $t) {
            if (Schema::hasColumn('modul','versi'))         $t->dropColumn('versi');
            if (Schema::hasColumn('modul','kategori'))      $t->dropColumn('kategori');
            if (Schema::hasColumn('modul','harga_default')) $t->dropColumn('harga_default');
        });
    }
};
