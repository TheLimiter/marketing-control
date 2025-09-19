<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tambah kolom-kolom baru bila belum ada
        Schema::table('activity_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_logs', 'title')) {
                $table->string('title')->nullable()->after('action');
            }
            if (! Schema::hasColumn('activity_logs', 'master_sekolah_id')) {
                $table->unsignedBigInteger('master_sekolah_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('activity_logs', 'before')) {
                $table->json('before')->nullable()->after('title');   // di SQLite jadi TEXT, tetap OK
            }
            if (! Schema::hasColumn('activity_logs', 'after')) {
                $table->json('after')->nullable()->after('before');
            }
            if (! Schema::hasColumn('activity_logs', 'ip')) {
                $table->string('ip', 64)->nullable()->after('after');
            }
            if (! Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Hapus balik kalau perlu (opsional, aman diabaikan di SQLite)
            if (Schema::hasColumn('activity_logs', 'user_agent')) $table->dropColumn('user_agent');
            if (Schema::hasColumn('activity_logs', 'ip'))         $table->dropColumn('ip');
            if (Schema::hasColumn('activity_logs', 'after'))      $table->dropColumn('after');
            if (Schema::hasColumn('activity_logs', 'before'))     $table->dropColumn('before');
            if (Schema::hasColumn('activity_logs', 'master_sekolah_id')) $table->dropColumn('master_sekolah_id');
            if (Schema::hasColumn('activity_logs', 'title'))      $table->dropColumn('title');
        });
    }
};
