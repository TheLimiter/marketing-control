<?php

namespace App\Policies;

use App\Models\TagihanKlien;
use App\Models\User;

class TagihanKlienPolicy
{
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, TagihanKlien $t): bool { return true; }

    public function create(User $u): bool
    {
        return (bool)($u->is_admin ?? false);
    }

    public function update(User $u, TagihanKlien $t): bool
    {
        return (bool)($u->is_admin ?? false) || $u->id === ($t->created_by ?? null);
    }

    public function delete(User $u, TagihanKlien $t): bool
    {
        // Jangan hapus kalau sudah ada pembayaran sebagian
        if ((int)$t->terbayar > 0) return false;
        return (bool)($u->is_admin ?? false) || $u->id === ($t->created_by ?? null);
    }

    // izin untuk aksi khusus "bayar"
    public function bayar(User $u, TagihanKlien $t): bool
    {
        return (bool)($u->is_admin ?? false) || $u->id === ($t->created_by ?? null);
    }
}
