<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('activity_logs', function (Blueprint $t) {
            // scope sekolah (opsional) → mempermudah filter/feed per-sekolah
            if (!Schema::hasColumn('activity_logs', 'master_sekolah_id')) {
                $t->unsignedBigInteger('master_sekolah_id')->nullable()->after('user_id');
                $t->index(['master_sekolah_id', 'created_at'], 'activity_school_time_idx');
                // Jika MySQL dan kamu yakin tabelnya ada, bisa aktifkan FK:
                // $t->foreign('master_sekolah_id')->references('id')->on('master_sekolah')->nullOnDelete();
            }

            // judul ringkas untuk UI feed (tanpa harus render dari before/after)
            if (!Schema::hasColumn('activity_logs', 'title')) {
                $t->string('title', 160)->nullable()->after('action');
            }

            // index tambahan pada action (kalau belum ada)
            // Laravel di migrasi awal sudah bikin index action, tapi aman kalau dobel try
        });
    }

    public function down(): void {
        Schema::table('activity_logs', function (Blueprint $t) {
            if (Schema::hasColumn('activity_logs', 'title')) $t->dropColumn('title');
            if (Schema::hasColumn('activity_logs', 'master_sekolah_id')) {
                $t->dropIndex('activity_school_time_idx');
                $t->dropColumn('master_sekolah_id');
            }
        });
    }
};
