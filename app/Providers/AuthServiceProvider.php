<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\{Modul, PenggunaanModul, TagihanKlien, ActivityLog, User};
use App\Policies\{ModulPolicy, PenggunaanModulPolicy, TagihanKlienPolicy, ActivityLogPolicy};

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Modul::class            => ModulPolicy::class,
        PenggunaanModul::class  => PenggunaanModulPolicy::class,
        TagihanKlien::class     => TagihanKlienPolicy::class,
        ActivityLog::class      => ActivityLogPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Pakai Spatie role sebagai sumber kebenaran admin
        Gate::define('isAdmin', fn(User $u) => $u->hasRole('admin'));
        Gate::define('delete-data', fn(User $u) => $u->hasRole('admin'));
    }
}
