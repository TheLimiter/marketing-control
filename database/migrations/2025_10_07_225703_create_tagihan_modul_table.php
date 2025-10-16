<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tagihan_modul')) {
            Schema::create('tagihan_modul', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tagihan_id');
                $t->unsignedBigInteger('modul_id');
                $t->string('keterangan', 255)->nullable();
                $t->timestamps();

                // index & constraints
                $t->unique(['tagihan_id','modul_id']);
                $t->foreign('tagihan_id')->references('id')->on('tagihan_klien')->onDelete('cascade');
                $t->foreign('modul_id')->references('id')->on('modul')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_modul');
    }
};
