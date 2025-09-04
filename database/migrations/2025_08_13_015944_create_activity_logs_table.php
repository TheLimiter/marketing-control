<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('entity_type', 50);   // calon, prospek, klien, tagihan, dll
            $t->unsignedBigInteger('entity_id');
            $t->string('action', 100);       // prospek.mou.update, prospek.ttd.set, prospek.to_klien, dll
            $t->json('before')->nullable();  // snapshot ringkas sebelum
            $t->json('after')->nullable();   // snapshot ringkas sesudah
            $t->string('ip', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();

            $t->index(['entity_type','entity_id']);
            $t->index(['action']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('activity_logs');
    }
};
