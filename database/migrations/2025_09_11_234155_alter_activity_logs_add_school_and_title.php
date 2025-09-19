<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('activity_logs', function (Blueprint $t) {
            // scope sekolah (opsional) â†’ mempermudah filter/feed per-sekolah
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
        if (Schema::hasColumn('activity_logs', 'title')) {
            $t->dropColumn('title');
        }
    });

    // Drop index & kolom master_sekolah_id dengan aman
    if (Schema::hasColumn('activity_logs', 'master_sekolah_id')) {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS activity_school_time_idx');
        } elseif ($driver === 'mysql') {
            // MySQL tidak punya IF EXISTS untuk DROP INDEX; cek ke information_schema
            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'activity_logs')
                ->where('index_name', 'activity_school_time_idx')
                ->exists();
            if ($exists) {
                DB::statement('DROP INDEX activity_school_time_idx ON activity_logs');
            }
        } elseif ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS activity_school_time_idx');
        }

        Schema::table('activity_logs', function (Blueprint $t) {
            $t->dropColumn('master_sekolah_id');
        });
    }
}
};
