<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aktivitas_prospek', function (Blueprint $table) {
            // Tambahkan baris ini
            $table->foreignId('modul_id')
                  ->nullable()
                  ->after('master_sekolah_id') // Opsional: menempatkan kolom setelah master_sekolah_id
                  ->constrained('modul') // Membuat foreign key ke tabel 'modul'
                  ->nullOnDelete(); // Jika modul dihapus, isi kolom ini jadi NULL
        });
    }

    public function down(): void
    {
        Schema::table('aktivitas_prospek', function (Blueprint $table) {
            // Laravel otomatis membuat ini untuk rollback
            $table->dropForeign(['modul_id']);
            $table->dropColumn('modul_id');
        });
    }
};
