<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['master_sekolah','aktivitas_prospek','penggunaan_modul','tagihan_klien','notifikasi'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if (!Schema::hasColumn($table, 'deleted_at')) {
                        $t->softDeletes();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['master_sekolah','aktivitas_prospek','penggunaan_modul','tagihan_klien','notifikasi'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if (Schema::hasColumn($table, 'deleted_at')) {
                        $t->dropSoftDeletes();
                    }
                });
            }
        }
    }
};
