<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('master_sekolah', function (Blueprint $table) {
            if (!Schema::hasColumn('master_sekolah', 'mou_path')) {
                $table->string('mou_path')->nullable();
            }
            if (!Schema::hasColumn('master_sekolah', 'ttd_status')) {
                $table->boolean('ttd_status')->default(false);
            }
            if (!Schema::hasColumn('master_sekolah', 'mou_catatan')) {
                $table->text('mou_catatan')->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('master_sekolah', function (Blueprint $table) {
            if (Schema::hasColumn('master_sekolah', 'mou_catatan')) $table->dropColumn('mou_catatan');
            if (Schema::hasColumn('master_sekolah', 'ttd_status'))  $table->dropColumn('ttd_status');
            if (Schema::hasColumn('master_sekolah', 'mou_path'))    $table->dropColumn('mou_path');
        });
    }
};
