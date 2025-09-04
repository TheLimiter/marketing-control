<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_pengguna', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('aktivitas', 50);            // CREATE / UPDATE / DELETE / LOGIN / LOGOUT / dll
            $t->text('keterangan')->nullable();     // “Tambah Calon Klien #3”
            // opsional info tambahan
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent')->nullable();
            $t->string('route')->nullable();
            $t->string('method', 10)->nullable();

            $t->timestamps();
            $t->index(['created_at']);             // buat filter/log audit lebih cepat
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_pengguna');
    }
};
