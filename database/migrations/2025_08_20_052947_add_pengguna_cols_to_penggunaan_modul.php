<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            if (!Schema::hasColumn('penggunaan_modul','pengguna_nama')) {
                $t->string('pengguna_nama',120)->nullable();
            }
            if (!Schema::hasColumn('penggunaan_modul','pengguna_kontak')) {
                $t->string('pengguna_kontak',120)->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('penggunaan_modul', function (Blueprint $t) {
            if (Schema::hasColumn('penggunaan_modul','pengguna_nama')) {
                $t->dropColumn('pengguna_nama');
            }
            if (Schema::hasColumn('penggunaan_modul','pengguna_kontak')) {
                $t->dropColumn('pengguna_kontak');
            }
        });
    }
};
