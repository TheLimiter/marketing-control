<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            if (! Schema::hasColumn('penggunaan_modul', 'stage_modul')) {
                // pakai string + index; biar fleksibel & gampang difilter
                $t->string('stage_modul', 20)->nullable()->index()
                  ->comment('Nilai: dilatih | didampingi | mandiri');
            }
        });

        // (Opsional) CHECK constraint kalau DB-mu support (MySQL 8.0.16+/MariaDB 10.2.1+/PostgreSQL)
        try {
            // Beri nama constraint yang unik dan gampang di-drop
            DB::statement("
                ALTER TABLE penggunaan_modul
                ADD CONSTRAINT chk_penggunaan_modul_stage
                CHECK (stage_modul IN ('dilatih','didampingi','mandiri') OR stage_modul IS NULL)
            ");
        } catch (\Throwable $e) {
            // Abaikan kalau engine tidak mendukung CHECK (tidak fatal)
        }
    }

    public function down(): void
    {
        // Drop CHECK kalau ada
        try {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE penggunaan_modul DROP CONSTRAINT chk_penggunaan_modul_stage");
        } catch (\Throwable $e) {
            try {
                // Beberapa engine butuh DROP CHECK <name>
                DB::statement("ALTER TABLE penggunaan_modul DROP CHECK chk_penggunaan_modul_stage");
            } catch (\Throwable $e2) {
                // abaikan
            }
        }

        Schema::table('penggunaan_modul', function (Blueprint $t) {
            if (Schema::hasColumn('penggunaan_modul', 'stage_modul')) {
                // Hapus index otomatis kalau ada (Laravel akan menghapus index inline)
                $t->dropColumn('stage_modul');
            }
        });
    }
};
