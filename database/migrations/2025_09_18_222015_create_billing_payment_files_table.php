<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_payment_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tagihan_id')->constrained('tagihan_klien')->cascadeOnDelete();
            // kalau kamu tetap mau tautkan ke log aktivitas pembayaran:
            $t->unsignedBigInteger('aktivitas_id')->nullable()->index(); // -> refer ke aktivitas_prospek.id (opsional)
            $t->string('path');           // storage path di disk 'public'
            $t->string('original_name');  // nama file asli
            $t->string('mime', 100)->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_files');
    }
};
