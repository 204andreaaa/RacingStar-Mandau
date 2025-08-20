<?php

// app/Http/Controllers/ManualAuthController.php
namespace App\Http\Controllers;

use App\Models\UserBestrising;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Route; 

class ManualAuthController extends Controller
{
    public function loginForm()
    {
        return view('bestRising.login.index');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        $user = UserBestrising::with('kategoriUser')
            ->where('email', $cred['email'])
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $raw = $cred['password'];
        $ok  = Hash::check($raw, $user->password)
               || (!preg_match('/^\$2y\$/', $user->password) && hash_equals($user->password, $raw));

        if (!$ok) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        // Optional: auto rehash jika sebelumnya plain text
        if (!preg_match('/^\$2y\$/', $user->password)) {
            $user->password = Hash::make($raw);
            $user->save();
        }

        $role = strtolower($user->kategoriUser->nama_kategoriuser ?? '');
        $isAdmin = in_array($role, ['admin','superadmin'], true);

        session(['auth_user' => [
            'id'            => $user->id_userBestrising,
            'nik'           => $user->nik,
            'nama'          => $user->nama,
            'email'         => $user->email,
            'kategori_id'   => $user->kategori_user_id,
            'id_region'     => $user->id_region,
            'id_serpo'      => $user->id_serpo,
            'id_segmen'     => $user->id_segmen,
            'kategori_nama' => $user->kategoriUser->nama_kategoriuser ?? null,
            'is_admin'      => $isAdmin,
        ]]);

        return $isAdmin
            ? redirect()->route('admin.home')      // atau route admin yang kamu mau
            : (Route::has('teknisi.index') ? redirect()->route('teknisi.index') : redirect('/teknisi'));  // ke views/bestRising/user/index.blade.php
    }

    public function logout(Request $request)
    {
        $request->session()->forget('auth_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
