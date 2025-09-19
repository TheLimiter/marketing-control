<?php

// database/migrations/2025_08_14_000000_create_master_sekolah_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_sekolah', function (Blueprint $t) {
            $t->id();
            $t->string('nama_sekolah');
            $t->text('alamat')->nullable();
            $t->string('no_hp', 30)->nullable();
            $t->string('sumber', 100)->nullable();
            $t->text('catatan')->nullable();
            $t->string('jenjang', 50)->nullable();
            $t->string('narahubung', 100)->nullable();

            // Ganti enum → string + default
            $t->string('status_klien', 20)->default('calon');

            $t->text('tindak_lanjut')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        // Tambahkan CHECK constraint khusus pgsql agar tetap terbatas ke 3 nilai
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE master_sekolah
                ADD CONSTRAINT master_sekolah_status_klien_check
                CHECK (status_klien IN ('calon','prospek','klien'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_sekolah');
    }
};
