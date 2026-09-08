<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Halaman ganti password milik sendiri — sengaja TIDAK dibatasi
     * middleware 'permission:...' apapun, karena setiap user (apapun
     * role-nya) berhak ganti password akunnya sendiri.
     */
    public function edit()
    {
        return view('profile.edit');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);
        }

        $user->password = $validated['password']; // ter-hash otomatis lewat cast 'hashed'
        $user->save();

        return back()->with('success', 'Password berhasil diubah.');
    }
}
