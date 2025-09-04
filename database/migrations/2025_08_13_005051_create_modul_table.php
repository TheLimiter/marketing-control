<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('modul', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->string('nama');
            $t->text('deskripsi')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('modul');
    }
};
