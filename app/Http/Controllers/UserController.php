<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(10);
        $roles = Role::pluck('name'); // Mengambil nama roles saja
        return view('users.index', compact('users', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);
        $user->syncRoles([$request->role]);
        return redirect()->route('users.index')->with('ok', 'Role pengguna diperbarui.');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // Beri peran default jika ada
        $user->assignRole('Marketing');

        return redirect()->route('users.index')->with('ok', 'Pengguna baru berhasil ditambahkan.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->hasRole('admin')) {
            throw ValidationException::withMessages(['error' => 'Tidak bisa menonaktifkan pengguna dengan peran Admin.']);
        }

        $user->active = !$user->active;
        $user->save();

        return back()->with('ok', 'Status pengguna berhasil diperbarui.');
    }
}
