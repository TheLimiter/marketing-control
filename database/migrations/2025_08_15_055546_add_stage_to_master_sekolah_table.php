<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('master_sekolah', function (Blueprint $table) {
            // stage: simpan sebagai smallint (PG tidak punya unsigned tinyint)
            if (! Schema::hasColumn('master_sekolah', 'stage')) {
                $table->smallInteger('stage')->default(1); // 1=calon
            }

            // timestamp perubahan stage
            if (! Schema::hasColumn('master_sekolah', 'stage_changed_at')) {
                $table->timestamp('stage_changed_at')->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('master_sekolah', function (Blueprint $table) {
            if (Schema::hasColumn('master_sekolah', 'stage_changed_at')) {
                $table->dropColumn('stage_changed_at');
            }
            if (Schema::hasColumn('master_sekolah', 'stage')) {
                $table->dropColumn('stage');
            }
        });
    }
};
