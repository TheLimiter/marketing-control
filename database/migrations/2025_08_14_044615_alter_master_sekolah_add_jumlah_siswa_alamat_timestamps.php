<?php

// database/migrations/2025_08_14_120000_alter_master_sekolah_add_jumlah_siswa_alamat_timestamps.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('master_sekolah', function (Blueprint $t) {
            // alamat (kalau belum ada)
            if (! Schema::hasColumn('master_sekolah', 'alamat')) {
                $t->text('alamat')->nullable();
            }

            // jumlah_siswa (integer biasa; PostgreSQL tidak punya unsigned)
            if (! Schema::hasColumn('master_sekolah', 'jumlah_siswa')) {
                $t->integer('jumlah_siswa')->nullable();
                // Jika ingin benar-benar non-negatif di PG saja, bisa tambahkan CHECK di migrasi terpisah.
            }

            // timestamps: tambahkan per kolom agar tidak “miss” salah satu
            if (! Schema::hasColumn('master_sekolah', 'created_at')) {
                $t->timestamp('created_at')->nullable();
            }
            if (! Schema::hasColumn('master_sekolah', 'updated_at')) {
                $t->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('master_sekolah', function (Blueprint $t) {
            if (Schema::hasColumn('master_sekolah', 'jumlah_siswa')) {
                $t->dropColumn('jumlah_siswa');
            }
            // alamat & timestamps sengaja tidak di-drop agar tidak mengganggu data yang sudah ada.
        });
    }
};
