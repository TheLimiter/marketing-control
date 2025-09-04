<?php

namespace App\Policies;

use App\Models\Modul;
use App\Models\User;
use App\Models\PenggunaanModul;

class ModulPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Modul $modul): bool { return true; }

    public function create(User $user): bool
    {
        return (bool)($user->is_admin ?? false);
    }

    public function update(User $user, Modul $modul): bool
    {
        return (bool)($user->is_admin ?? false);
    }

    public function delete(User $user, Modul $modul): bool
    {
        if (!($user->is_admin ?? false)) return false;

        // blok kalau sudah dipakai
        $inUse = PenggunaanModul::where('modul_id', $modul->id)->exists();
        return !$inUse;
    }
}
