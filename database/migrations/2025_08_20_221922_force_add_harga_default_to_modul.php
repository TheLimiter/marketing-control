<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // DETECT driver
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // Cek lagi supaya aman
            $has = collect(DB::select("PRAGMA table_info('modul')"))
                ->contains(fn($c) => strtolower($c->name) === 'harga_default');

            if (!$has) {
                // SQLite: ADD COLUMN pasti sukses
                DB::statement("ALTER TABLE modul ADD COLUMN harga_default INTEGER NOT NULL DEFAULT 0");
            }
        } else {
            // MySQL / lainnya
            Schema::table('modul', function (Blueprint $t) {
                if (!Schema::hasColumn('modul','harga_default')) {
                    $t->unsignedBigInteger('harga_default')->default(0)->after('deskripsi');
                }
            });
        }
    }

    public function down(): void {
        // Optional: SQLite susah drop column tanpa rebuild table — biarin saja.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('modul', function (Blueprint $t) {
                if (Schema::hasColumn('modul','harga_default')) $t->dropColumn('harga_default');
            });
        }
    }
};
