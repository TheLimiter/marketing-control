<?php

namespace App\Policies;

use App\Models\TagihanKlien;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TagihanKlienPolicy
{
    /**
     * Perform pre-authorization checks.
     * Memberikan akses penuh kepada admin untuk semua aksi.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Menggunakan hasRole() adalah cara yang lebih standar dan aman
        if ($user->hasRole('admin')) {
            return true;
        }
        return null; // Biarkan metode lain yang memutuskan untuk non-admin
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Semua pengguna yang login bisa melihat daftar tagihan
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TagihanKlien $tagihanKlien): bool
    {
        // Non-admin hanya bisa melihat tagihan yang mereka buat sendiri.
        // Admin sudah diizinkan oleh metode before().
        return $user->id === $tagihanKlien->created_by;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Semua pengguna yang login diizinkan membuat tagihan.
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TagihanKlien $tagihanKlien): bool
    {
        // Non-admin hanya bisa mengedit tagihan yang mereka buat.
        return $user->id === $tagihanKlien->created_by;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TagihanKlien $tagihanKlien): bool
    {
        // Aturan ini sekarang hanya berlaku untuk non-admin.
        // 1. Tidak boleh hapus jika sudah ada pembayaran.
        if ((int)$tagihanKlien->terbayar > 0) {
            return false;
        }
        // 2. Harus pembuatnya.
        return $user->id === $tagihanKlien->created_by;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TagihanKlien $tagihanKlien): bool
    {
        // Hanya admin yang bisa force delete, sudah ditangani oleh before().
        // Non-admin tidak diizinkan sama sekali.
        return false;
    }

    /**
     * Izin untuk aksi khusus "bayar"
     */
    public function bayar(User $user, TagihanKlien $tagihanKlien): bool
    {
        // Non-admin hanya bisa membayar tagihan yang mereka buat.
        return $user->id === $tagihanKlien->created_by;
    }
}
