<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('modul', function (Blueprint $t) {
            if (!Schema::hasColumn('modul','kategori')) {
                $t->string('kategori', 50)->nullable()->after('nama');
            }
            if (!Schema::hasColumn('modul','versi')) {
                $t->string('versi', 50)->nullable()->after('kategori');
            }
            if (!Schema::hasColumn('modul','deskripsi')) {
                $t->text('deskripsi')->nullable()->after('versi');
            }
        });
    }

    public function down(): void {
        Schema::table('modul', function (Blueprint $t) {
            if (Schema::hasColumn('modul','deskripsi')) $t->dropColumn('deskripsi');
            if (Schema::hasColumn('modul','versi'))     $t->dropColumn('versi');
            if (Schema::hasColumn('modul','kategori'))  $t->dropColumn('kategori');
        });
    }
};
