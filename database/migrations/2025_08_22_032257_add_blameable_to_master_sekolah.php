<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('master_sekolah')) {
            Schema::table('master_sekolah', function (Blueprint $table) {
                if (! Schema::hasColumn('master_sekolah', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('jumlah_siswa');
                }
                if (! Schema::hasColumn('master_sekolah', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('master_sekolah')) {
            Schema::table('master_sekolah', function (Blueprint $table) {
                if (Schema::hasColumn('master_sekolah', 'created_by')) $table->dropColumn('created_by');
                if (Schema::hasColumn('master_sekolah', 'updated_by')) $table->dropColumn('updated_by');
            });
        }
    }
};
