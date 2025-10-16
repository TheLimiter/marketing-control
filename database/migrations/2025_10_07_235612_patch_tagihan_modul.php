<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Buat tabel jika belum ada sama sekali
        if (!Schema::hasTable('tagihan_modul')) {
            Schema::create('tagihan_modul', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tagihan_id');
                $t->unsignedBigInteger('modul_id');
                $t->string('keterangan', 255)->nullable();
                $t->timestamps();

                $t->unique(['tagihan_id','modul_id']);
                $t->foreign('tagihan_id')->references('id')->on('tagihan_klien')->onDelete('cascade');
                $t->foreign('modul_id')->references('id')->on('modul')->onDelete('cascade');
            });
            return;
        }

        // Kalau tabelnya sudah ada tapi kolomnya beda, samakan
        Schema::table('tagihan_modul', function (Blueprint $t) {
            if (!Schema::hasColumn('tagihan_modul','tagihan_id')) {
                // Tambah dulu kolom yang benar
                $t->unsignedBigInteger('tagihan_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('tagihan_modul','modul_id')) {
                $t->unsignedBigInteger('modul_id')->nullable();
            }
            if (!Schema::hasColumn('tagihan_modul','keterangan')) {
                $t->string('keterangan',255)->nullable();
            }
            if (!Schema::hasColumn('tagihan_modul','created_at')) {
                $t->timestamps();
            }
        });

        // Migrasikan data lama jika punya kolom lama 'tagihan_klien_id'
        if (Schema::hasColumn('tagihan_modul','tagihan_klien_id')) {
            DB::statement('UPDATE tagihan_modul SET tagihan_id = tagihan_klien_id WHERE tagihan_id IS NULL');
            // coba drop FK lama kalau ada (nama bisa beda, jadi ignore error)
            try { DB::statement('ALTER TABLE tagihan_modul DROP CONSTRAINT IF EXISTS tagihan_modul_tagihan_klien_id_foreign'); } catch (\Throwable $e) {}
            Schema::table('tagihan_modul', function (Blueprint $t) {
                $t->dropColumn('tagihan_klien_id');
            });
        }

        // Buat indeks unik & FK (abaikan jika sudah ada)
        try { DB::statement('CREATE UNIQUE INDEX tagihan_modul_tagihan_modul_unique ON tagihan_modul(tagihan_id, modul_id)'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tagihan_modul ADD CONSTRAINT tagihan_modul_tagihan_fk FOREIGN KEY (tagihan_id) REFERENCES tagihan_klien(id) ON DELETE CASCADE'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tagihan_modul ADD CONSTRAINT tagihan_modul_modul_fk FOREIGN KEY (modul_id) REFERENCES modul(id) ON DELETE CASCADE'); } catch (\Throwable $e) {}

        // NOT NULL kalau sudah aman
        try { DB::statement('ALTER TABLE tagihan_modul ALTER COLUMN tagihan_id SET NOT NULL'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tagihan_modul ALTER COLUMN modul_id SET NOT NULL'); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // tidak perlu rollback khusus
    }
};
