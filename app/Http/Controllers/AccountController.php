<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function editPassword()
    {
        return view('account.password');
    }

    public function updatePassword(Request $r)
    {
        $r->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        $u = $r->user();
        $u->forceFill([
            'password' => Hash::make($r->password),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')->with('ok','Password berhasil diperbarui.');
    }
}
