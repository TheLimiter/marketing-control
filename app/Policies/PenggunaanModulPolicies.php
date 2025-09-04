<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PenggunaanModul as PM;

class PenggunaanModulPolicy
{
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, PM $pm): bool { return true; }

    public function create(User $u): bool
    {
        return (bool)($u->is_admin ?? false);
    }

    public function update(User $u, PM $pm): bool
    {
        return (bool)($u->is_admin ?? false) || $u->id === $pm->created_by;
    }

    public function delete(User $u, PM $pm): bool
    {
        // Aman: jangan hapus yang statusnya aktif
        if ($pm->status === 'active') return false;
        return (bool)($u->is_admin ?? false) || $u->id === $pm->created_by;
    }
}
