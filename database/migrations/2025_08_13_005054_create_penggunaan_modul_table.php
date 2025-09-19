<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('penggunaan_modul', function (Blueprint $table) {
            $table->id();

            $table->foreignId('master_sekolah_id')
                  ->constrained('master_sekolah')->cascadeOnDelete();

            $table->foreignId('modul_id')
                  ->constrained('modul')->cascadeOnDelete();

            // PIC/Narahubung modul
            $table->string('pengguna_nama', 120)->nullable();
            $table->string('pengguna_kontak', 120)->nullable();

            // Periode pemakaian
            $table->date('mulai_tanggal');
            $table->date('akhir_tanggal')->nullable();

            // Lisensi & status
            $table->boolean('is_official')->default(false);
            $table->string('status', 20)->default('active'); // <- tetap 'active'

            // Info lain
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->softDeletes();

            // Satu baris per (sekolah, modul)
            $table->unique(['master_sekolah_id','modul_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('penggunaan_modul');
    }
};
