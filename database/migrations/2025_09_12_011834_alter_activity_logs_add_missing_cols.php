<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Tambah kolom jika belum ada (aman untuk sqlite)
            if (! Schema::hasColumn('activity_logs', 'type')) {
                $table->string('type', 100)->nullable()->after('id');
            }
            if (! Schema::hasColumn('activity_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('type');
            }
            if (! Schema::hasColumn('activity_logs', 'subject_type')) {
                $table->string('subject_type', 191)->nullable()->after('subject_id');
            }
            if (! Schema::hasColumn('activity_logs', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('subject_type');
            }
            if (! Schema::hasColumn('activity_logs', 'before')) {
                $table->text('before')->nullable()->after('school_id');
            }
            if (! Schema::hasColumn('activity_logs', 'after')) {
                $table->text('after')->nullable()->after('before');
            }
            if (! Schema::hasColumn('activity_logs', 'message')) {
                $table->text('message')->nullable()->after('after');
            }
            if (! Schema::hasColumn('activity_logs', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('message');
            }
            if (! Schema::hasColumn('activity_logs', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            // Timestamps kalau belum ada
            if (! Schema::hasColumn('activity_logs', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop kolom yang kita tambahkan (abaikan bila tidak ada)
            foreach ([
                'type','subject_id','subject_type','school_id',
                'before','after','message','created_by','updated_by'
            ] as $col) {
                if (Schema::hasColumn('activity_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
