<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserAdminController extends Controller
{
    /**
     * List user + filter + kirim daftar roles ke view.
     */
    public function index(Request $r)
    {
        $q = User::with('roles')->orderBy('name');

        // Filter pencarian
        if ($r->filled('q')) {
            $term = trim($r->q);
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                   ->orWhere('email', 'like', "%{$term}%");
            });
        }

        // Filter role
        if ($r->filled('role')) {
            $role = $r->role;
            $q->whereHas('roles', fn ($qr) => $qr->where('name', $role));
        }

        // Filter status aktif/nonaktif
        if ($r->has('status') && $r->status !== '') {
            $q->where('active', (bool) $r->status);
        }

        $users = $q->paginate(15)->withQueryString();
        $roles = Role::pluck('name'); // untuk dropdown role di view

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        return view('users.create');
    }

    /**
     * Buat user baru (default role: Marketing).
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'name'     => ['required','string','max:100'],
            'email'    => ['required','email','max:150','unique:users,email'],
            'password' => ['nullable','string','min:8'], // jika kosong, auto-generate
        ]);

        $plain = $data['password'] ?? Str::password(10);

        $user = User::create([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'password'             => Hash::make($plain),
            'must_change_password' => true,
        ]);

        // Beri role default (abaikan jika role belum ada)
        try {
            $user->assignRole('Marketing');
        } catch (\Throwable $e) {
            // ignore silently jika role 'Marketing' belum dibuat
        }

        return redirect()->route('admin.users.index')
            ->with('ok', "User dibuat. Password sementara: {$plain}");
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Flexible:
     * - Jika request membawa 'role' -> update role
     * - Selain itu -> update name/email
     */
    public function update(Request $r, User $user)
    {
        if ($r->has('role')) {
            // Update role
            $data = $r->validate([
                'role' => ['required','exists:roles,name'],
            ]);
            $user->syncRoles([$data['role']]);

            return back()->with('ok', 'Role pengguna diperbarui.');
        }

        // Update basic profile
        $data = $r->validate([
            'name'  => ['required','string','max:100'],
            'email' => ['required','email','max:150', Rule::unique('users','email')->ignore($user->id)],
        ]);

        $user->update($data);
        return redirect()->route('admin.users.index')->with('ok', 'User diperbarui.');
    }

    public function destroy(User $user)
    {
        // Hindari hapus diri sendiri
        if (auth()->id() === $user->id) {
            return back()->with('err','Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();
        return back()->with('ok','User dihapus.');
    }

    /**
     * Toggle aktif/nonaktif (proteksi admin & diri sendiri).
     */
    public function toggleStatus(User $user)
    {
        if (auth()->id() === $user->id) {
            throw ValidationException::withMessages(['error' => 'Tidak bisa menonaktifkan akun sendiri.']);
        }

        if ($user->hasRole('admin')) {
            throw ValidationException::withMessages(['error' => 'Tidak bisa menonaktifkan pengguna dengan peran Admin.']);
        }

        $user->active = ! $user->active;
        $user->save();

        return back()->with('ok', 'Status pengguna berhasil diperbarui.');
    }

    public function resetPassword(User $user)
    {
        $plain = Str::password(10);
        $user->update([
            'password' => Hash::make($plain),
            'must_change_password' => true,
        ]);
        return back()->with('ok', "Password di-reset. Password sementara: {$plain}");
    }

    public function sendResetLink(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);
        return back()->with('ok',
            $status === Password::RESET_LINK_SENT
                ? 'Link reset password dikirim ke email user.'
                : 'Gagal mengirim link reset (cek konfigurasi email).'
        );
    }
}
