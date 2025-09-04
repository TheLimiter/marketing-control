<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Str;

class UserAdminController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => ['required','string','max:100'],
            'email'=> ['required','email','max:150','unique:users,email'],
            'password' => ['nullable','string','min:8'], // jika kosong, auto-generate
        ]);

        $plain = $data['password'] ?? Str::password(10);
        $user = User::create([
            'name' => $data['name'],
            'email'=> $data['email'],
            'password' => Hash::make($plain),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('ok', "User dibuat. Password sementara: {$plain}");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $r, User $user)
    {
        $data = $r->validate([
            'name' => ['required','string','max:100'],
            'email'=> ['required','email','max:150', Rule::unique('users','email')->ignore($user->id)],
        ]);
        $user->update($data);
        return redirect()->route('admin.users.index')->with('ok','User diperbarui.');
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
        return back()->with('ok', $status === Password::RESET_LINK_SENT
            ? 'Link reset password dikirim ke email user.'
            : 'Gagal mengirim link reset (cek konfigurasi email).');
    }
}
