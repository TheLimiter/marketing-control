<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifikasi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tagihan_id')->constrained('tagihan_klien')->cascadeOnDelete();
            $t->enum('saluran', ['Email','WA','SMS']);
            $t->enum('status', ['Antri','Terkirim','Gagal'])->default('Antri');
            $t->text('isi_pesan')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('notifikasi');
    }
};
