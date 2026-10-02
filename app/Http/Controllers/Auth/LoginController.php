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
        // Login memakai USERNAME (2026-09-23, feedback user) — email tetap
        // tersimpan untuk data & pemulihan akun, tapi tidak dipakai login.
        $credentials = $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ]);
        $credentials['username'] = mb_strtolower(trim($credentials['username']));

        // Cek dulu apakah user ada tapi nonaktif -> beri pesan spesifik,
        // supaya tidak membingungkan user pikir passwordnya yang salah.
        $user = \App\Models\User::where('username', $credentials['username'])->first();
        if ($user && !$user->is_active) {
            return back()->withErrors([
                'username' => 'Akun ini telah dinonaktifkan. Hubungi Administrator.',
            ])->onlyInput('username');
        }

        $remember = $request->boolean('remember');
        if (!Auth::attempt($credentials, $remember)) {
            return back()->withErrors([
                'username' => 'Username atau password yang Anda masukkan salah.',
            ])->onlyInput('username');
        }

        // Cookie "Ingat saya" bawaan Laravel bertahan 5 tahun — dicatat jam
        // terbitnya di sini supaya EnsureRememberNotExpired bisa memaksa
        // login ulang maksimal 24 jam kemudian, walau PC dimatikan
        // (2026-10-02, permintaan admin). Login tanpa centang "Ingat saya"
        // tidak terpengaruh kolom ini sama sekali.
        if ($remember) {
            Auth::user()->forceFill(['remember_token_issued_at' => now()])->save();
        }

        $request->session()->regenerate();
       
        \App\Helpers\AuditLogger::record('auth.login', 'Login berhasil');

        // Beranda = dashboard operasional yang bisa dibuka semua role.
        // SELALU ke Beranda (2026-09-15, feedback user). Sebelumnya pakai
        // intended(), yang mengembalikan user ke halaman terakhir yang sempat
        // dibuka sebelum sesi habis — sering List Project, bukan Beranda.
        // Flash "welcome" memunculkan layar sambutan gelap sekali saja di
        // halaman berikutnya (2026-09-15, feedback user) — lihat layouts/app.
        return redirect()->route('home')->with('welcome', true);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil keluar.');
    }
}
