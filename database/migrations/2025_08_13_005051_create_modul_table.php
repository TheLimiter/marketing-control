<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('modul')) {
            Schema::create('modul', function (Blueprint $t) {
                $t->id();
                $t->string('kode'); // unique ditangani per-driver di bawah
                $t->string('nama');
                $t->text('deskripsi')->nullable();
                $t->boolean('aktif')->default(true);
                $t->timestamps();

            });

            // Unique untuk kode:
            if (DB::getDriverName() === 'pgsql') {
                // case-insensitive unique di PG
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS modul_kode_unique_ci ON modul (LOWER(kode));');
            } else {
                // MySQL/MariaDB (default collation CI): cukup unique biasa
                Schema::table('modul', fn (Blueprint $t) => $t->unique('kode', 'modul_kode_unique'));
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('modul')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS modul_kode_unique_ci');
            } else {
                Schema::table('modul', fn (Blueprint $t) => $t->dropUnique('modul_kode_unique'));
            }
            Schema::dropIfExists('modul');
        }
    }
};
