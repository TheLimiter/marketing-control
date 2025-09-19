<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_logs')) return;

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite & PG punya IF NOT EXISTS → idempotent
            DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_master_sekolah_id_index ON activity_logs (master_sekolah_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_created_at_index ON activity_logs (created_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_user_id_index ON activity_logs (user_id)');
        } elseif ($driver === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_master_sekolah_id_index ON activity_logs (master_sekolah_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_created_at_index ON activity_logs (created_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_user_id_index ON activity_logs (user_id)');
        } else {
            // MySQL/MariaDB: cek seadanya via try/catch (eksekusinya di luar closure)
            Schema::table('activity_logs', function (Blueprint $t) {
                try { $t->index('master_sekolah_id'); } catch (\Throwable $e) {}
                try { $t->index('created_at'); }        catch (\Throwable $e) {}
                try { $t->index('user_id'); }           catch (\Throwable $e) {}
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('activity_logs')) return;

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite' || $driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS activity_logs_master_sekolah_id_index');
            DB::statement('DROP INDEX IF EXISTS activity_logs_created_at_index');
            DB::statement('DROP INDEX IF EXISTS activity_logs_user_id_index');
        } else {
            Schema::table('activity_logs', function (Blueprint $t) {
                foreach ([
                    'activity_logs_master_sekolah_id_index',
                    'activity_logs_created_at_index',
                    'activity_logs_user_id_index',
                ] as $name) {
                    try { $t->dropIndex($name); } catch (\Throwable $e) {}
                }
            });
        }
    }
};
