<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tagihan_klien', function (Blueprint $table) {
            $table->id();

            // FK yang benar: ke master_sekolah
            $table->foreignId('master_sekolah_id')
                  ->constrained('master_sekolah')->cascadeOnDelete();

            $table->string('nomor')->unique();
            $table->date('tanggal_tagihan');
            $table->date('jatuh_tempo')->nullable();
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('terbayar')->default(0);
            $table->string('status', 50)->default('draft'); // draft, terkirim, lunas, dll
            $table->text('catatan')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tagihan_klien');
    }
};
