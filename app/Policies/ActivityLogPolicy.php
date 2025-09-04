<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    /** Admin boleh semuanya; user biasa dibatasi */
    public function viewAny(User $user): bool
    {
        return (bool)($user->is_admin ?? false);
    }

    public function view(User $user, ActivityLog $log): bool
    {
        // Admin boleh, user biasa hanya boleh lihat log dirinya sendiri
        return (bool)($user->is_admin ?? false) || $log->user_id === $user->id;
    }

    public function delete(User $user, ActivityLog $log): bool
    {
        return (bool)($user->is_admin ?? false);
    }

    // kalau mau ada aksi "clear all" (custom ability)
    public function clear(User $user): bool
    {
        return (bool)($user->is_admin ?? false);
    }
}
