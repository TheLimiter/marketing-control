<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('master_sekolah', function (Blueprint $table) {
            if (! Schema::hasColumn('master_sekolah', 'stage')) {
                $table->unsignedTinyInteger('stage')->default(1)->after('id'); // 1=calon
            }
            if (! Schema::hasColumn('master_sekolah', 'stage_changed_at')) {
                $table->timestamp('stage_changed_at')->nullable()->after('stage');
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
