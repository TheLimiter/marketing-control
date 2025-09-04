<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tagihan_klien', function (Blueprint $table) {
            if (!Schema::hasColumn('tagihan_klien', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tagihan_klien', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            // kalau mau: $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            //           $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_klien', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_klien', 'updated_by')) $table->dropColumn('updated_by');
            if (Schema::hasColumn('tagihan_klien', 'created_by')) $table->dropColumn('created_by');
        });
    }
};
