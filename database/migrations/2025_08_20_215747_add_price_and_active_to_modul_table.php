<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('modul')) return;

        Schema::table('modul', function (Blueprint $t) {
            if (!Schema::hasColumn('modul','harga_default')) {
                // MySQL-only 'after' → pakai jika kolomnya ada
                if (Schema::hasColumn('modul','deskripsi')) {
                    $t->unsignedBigInteger('harga_default')->default(0)->after('deskripsi');
                } else {
                    $t->unsignedBigInteger('harga_default')->default(0);
                }
            }
            if (!Schema::hasColumn('modul','aktif')) {
                if (Schema::hasColumn('modul','harga_default')) {
                    $t->boolean('aktif')->default(true)->after('harga_default');
                } else {
                    $t->boolean('aktif')->default(true);
                }
            }
        });
    }

    public function down(): void {
        if (!Schema::hasTable('modul')) return;

        Schema::table('modul', function (Blueprint $t) {
            if (Schema::hasColumn('modul','aktif'))         $t->dropColumn('aktif');
            if (Schema::hasColumn('modul','harga_default')) $t->dropColumn('harga_default');
        });
    }
};
