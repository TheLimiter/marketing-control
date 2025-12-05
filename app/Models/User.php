<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    /**
     * Cek apakah user admin (menggunakan Spatie)
     */
    public function isAdmin(): bool
    {
        // Cek role bernama 'admin' (pastikan di seeder nama rolenya 'admin' lowercase)
        return $this->hasRole('admin');
    }
    
    /**
     * Scope untuk pencarian (opsional, biar controller lebih rapi)
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}