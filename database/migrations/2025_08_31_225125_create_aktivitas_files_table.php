<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aktivitas_files', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('aktivitas_id')->index();
            $t->string('path');                 // storage path (public disk)
            $t->string('original_name');       // nama asli file
            $t->unsignedBigInteger('size')->default(0); // bytes
            $t->string('mime')->nullable();    // mime type
            $t->timestamps();

            $t->foreign('aktivitas_id')->references('id')->on('aktivitas_prospek')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('aktivitas_files');
    }
};
