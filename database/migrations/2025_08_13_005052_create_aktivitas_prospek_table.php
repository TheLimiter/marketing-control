<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('aktivitas_prospek', function (Blueprint $table) {
            $table->id();

            // RELASI YANG DIPAKAI
            $table->foreignId('master_sekolah_id')
                  ->constrained('master_sekolah')->cascadeOnDelete();

            $table->dateTime('tanggal')->index();
            $table->string('jenis', 100);     // mis. Follow Up, Meeting, dsb
            $table->string('hasil', 100)->nullable();
            $table->text('catatan')->nullable();

            // optional: siapa yang membuat
            $table->foreignId('created_by')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('aktivitas_prospek');
    }
};
