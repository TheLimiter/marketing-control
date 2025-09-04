<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            if (!Schema::hasColumn('penggunaan_modul', 'status')) {
                $t->string('status', 20)->default('attached')->index();
            }
            if (!Schema::hasColumn('penggunaan_modul', 'started_at')) {
                $t->timestamp('started_at')->nullable()->index();
            }
            if (!Schema::hasColumn('penggunaan_modul', 'finished_at')) {
                $t->timestamp('finished_at')->nullable()->index();
            }
            if (!Schema::hasColumn('penggunaan_modul', 'reopened_at')) {
                $t->timestamp('reopened_at')->nullable()->index();
            }
            if (!Schema::hasColumn('penggunaan_modul', 'notes')) {
                $t->text('notes')->nullable();
            }
            if (!Schema::hasColumn('penggunaan_modul', 'deleted_at')) {
                $t->softDeletes(); // SoftDeletes
            }
        });
    }

    public function down(): void
    {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            // turunkan hanya yang kita tambah
            foreach (['status','started_at','finished_at','reopened_at','notes','deleted_at'] as $c) {
                if (Schema::hasColumn('penggunaan_modul', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
