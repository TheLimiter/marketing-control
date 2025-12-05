<?php

namespace App\Http\Controllers\Admin; // Sesuaikan namespace jika folder Admin

use App\Http\Controllers\Controller; // Pastikan import ini ada
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')->latest();

        // 1. Filter Pencarian (Nama / Email)
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Filter Role
        if ($request->filled('role')) {
            $roleName = $request->role;
            $query->whereHas('roles', function (Builder $q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        // 3. Filter Status (Aktif/Nonaktif)
        if ($request->filled('status')) {
            $query->where('active', (bool) $request->status);
        }

        $users = $query->paginate(10)->withQueryString(); // withQueryString agar paginasi tidak reset filter
        $roles = Role::pluck('name'); 

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        // Cegah ubah role diri sendiri agar tidak terkunci
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role Anda sendiri.');
        }

        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user->syncRoles([$request->role]);
        
        return back()->with('ok', 'Role pengguna diperbarui.');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'     => $validatedData['name'],
            'email'    => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'active'   => true, // Default aktif
        ]);

        // Beri peran default
        $user->assignRole('marketing'); // Pastikan lowercase/sesuai nama di DB

        return back()->with('ok', 'Pengguna baru berhasil ditambahkan.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        // Cegah nonaktifkan diri sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        // Cek admin pakai Spatie method
        if ($user->hasRole('admin')) {
             return back()->with('error', 'Tidak bisa menonaktifkan Super Admin.');
        }

        $user->active = !$user->active;
        $user->save();

        $status = $user->active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('ok', "Pengguna berhasil $status.");
    }
    
    // Fitur Reset Password (sesuai route yang kita buat sebelumnya)
    public function resetPassword(Request $request, User $user)
    {
        // Generate random password 8 karakter
        $newPassword = \Illuminate\Support\Str::random(8);
        
        $user->password = Hash::make($newPassword);
        $user->save();

        return back()->with('ok', "Password berhasil direset. Password sementara: " . $newPassword);
    }
}