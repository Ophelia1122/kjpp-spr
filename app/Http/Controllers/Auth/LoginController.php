<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Cek dulu apakah user ada tapi nonaktif -> beri pesan spesifik,
        // supaya tidak membingungkan user pikir passwordnya yang salah.
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        if ($user && !$user->is_active) {
            return back()->withErrors([
                'email' => 'Akun ini telah dinonaktifkan. Hubungi Administrator.',
            ])->onlyInput('email');
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password yang Anda masukkan salah.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
       
        \App\Helpers\AuditLogger::record('auth.login', 'Login berhasil');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil keluar.');
    }
}
