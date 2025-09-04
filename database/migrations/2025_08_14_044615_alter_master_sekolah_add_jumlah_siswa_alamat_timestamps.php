<?php

// database/migrations/2025_08_14_120000_alter_master_sekolah_add_jumlah_siswa_alamat_timestamps.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('master_sekolah', function (Blueprint $t) {
            // alamat (kalau belum ada)
            if (!Schema::hasColumn('master_sekolah', 'alamat')) {
                $t->text('alamat')->nullable()->after('nama_sekolah');
            }

            // jumlah_siswa
            if (!Schema::hasColumn('master_sekolah', 'jumlah_siswa')) {
                $t->unsignedInteger('jumlah_siswa')->nullable()->after('narahubung');
            }

            // timestamps (dibuat pada / dirubah pada)
            // kalau sebelumnya belum ada created_at & updated_at
            if (!Schema::hasColumn('master_sekolah', 'created_at') && !Schema::hasColumn('master_sekolah', 'updated_at')) {
                $t->timestamps(); // created_at, updated_at
            }
        });
    }

    public function down(): void {
        Schema::table('master_sekolah', function (Blueprint $t) {
            if (Schema::hasColumn('master_sekolah', 'jumlah_siswa')) {
                $t->dropColumn('jumlah_siswa');
            }
            // alamat & timestamps biasanya tidak kita drop saat rollback skema tambah-kolom.
            // Tapi kalau kamu pengen strict, bisa tambahkan drop di sini.
        });
    }
};


