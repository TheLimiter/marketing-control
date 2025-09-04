<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        // pastikan cache permission clear
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ADMIN
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('admin123'), // ganti kalau perlu
            ]
        );
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }
        // kalau ada kolom is_admin, set true
        if (Schema::hasColumn('users', 'is_admin')) {
            $admin->forceFill(['is_admin' => 1])->save();
        }

        // USER BIASA
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'User Biasa',
                'password' => Hash::make('user12345'),
            ]
        );
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('user');
        }
    }
}
