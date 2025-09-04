<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('modul', function (Blueprint $t) {
            if (!Schema::hasColumn('modul','harga_default')) {
                // simpan dalam rupiah (integer), default 0
                $t->unsignedBigInteger('harga_default')->default(0)->after('deskripsi');
            }
            if (!Schema::hasColumn('modul','aktif')) {
                $t->boolean('aktif')->default(true)->after('harga_default');
            }
        });
    }

    public function down(): void {
        Schema::table('modul', function (Blueprint $t) {
            if (Schema::hasColumn('modul','aktif')) $t->dropColumn('aktif');
            if (Schema::hasColumn('modul','harga_default')) $t->dropColumn('harga_default');
        });
    }
};
