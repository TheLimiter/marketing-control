<?php

// database/migrations/2025_08_14_000000_create_master_sekolah_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('master_sekolah', function (Blueprint $t) {
            $t->id();
            $t->string('nama_sekolah');
            $t->text('alamat')->nullable();
            $t->string('no_hp', 30)->nullable();
            $t->string('sumber', 100)->nullable();
            $t->text('catatan')->nullable();
            $t->string('jenjang', 50)->nullable();
            $t->string('narahubung', 100)->nullable();
            $t->enum('status_klien', ['calon','prospek','klien'])->default('calon'); // kunci penyatu 👈
            $t->text('tindak_lanjut')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('master_sekolah');
    }
};
