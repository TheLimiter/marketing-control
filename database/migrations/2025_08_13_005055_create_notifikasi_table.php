<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifikasi', function (Blueprint $t) {
            $t->id();

            $t->foreignId('tagihan_id')
              ->constrained('tagihan_klien')
              ->cascadeOnDelete();

            // ENUM-compatible
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $t->string('saluran', 10);
                $t->string('status', 10)->default('Antri');
            } else {
                $t->enum('saluran', ['Email','WA','SMS']);
                $t->enum('status', ['Antri','Terkirim','Gagal'])->default('Antri');
            }

            $t->text('isi_pesan')->nullable();
            $t->timestamp('sent_at')->nullable();

            // dipakai di controller
            $t->foreignId('created_by')->nullable()
              ->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()
              ->constrained('users')->nullOnDelete();

            $t->timestamps();
            $t->softDeletes();
        });

        // CHECK constraint untuk Postgres (menggantikan enum)
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE notifikasi
                ADD CONSTRAINT notifikasi_saluran_check
                CHECK (saluran IN ('Email','WA','SMS'))");
            DB::statement("ALTER TABLE notifikasi
                ADD CONSTRAINT notifikasi_status_check
                CHECK (status IN ('Antri','Terkirim','Gagal'))");
        }
    }

    public function down(): void {
        Schema::dropIfExists('notifikasi');
    }
};
