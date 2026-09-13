<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * "Profil Saya" — halaman biodata + ganti password milik sendiri.
 * SENGAJA tanpa middleware permission apa pun: setiap user (apapun role-nya)
 * berhak melihat & mengubah biodata + password akunnya sendiri. Role &
 * status aktif TIDAK bisa diubah dari sini (itu ranah Kelola Pengguna).
 */
class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'jabatan'        => ['nullable', Rule::in(User::JABATAN_OPTIONS)],
            'partner_status' => 'nullable|string|max:255',
            'mappi_no'       => 'nullable|string|max:255',
            'rmk_no'         => 'nullable|string|max:255',
            'izin_menkeu_no' => 'nullable|string|max:255',
            'sk_menkeu_no'   => 'nullable|string|max:255',
            'sttd_ojk_no'    => 'nullable|string|max:255',
            'sk_menkeu_date' => 'nullable|date',
            'sttd_ojk_date'  => 'nullable|date',
            'klasifikasi'    => 'nullable|string|max:255',
        ]);

        $user->fill([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'jabatan'        => $validated['jabatan'] ?? null,
            'partner_status' => $validated['partner_status'] ?? null,
            'mappi_no'       => $validated['mappi_no'] ?? null,
            'rmk_no'         => $validated['rmk_no'] ?? null,
            'izin_menkeu_no' => $validated['izin_menkeu_no'] ?? null,
            'sk_menkeu_no'   => $validated['sk_menkeu_no'] ?? null,
            'sttd_ojk_no'    => $validated['sttd_ojk_no'] ?? null,
            'sk_menkeu_date' => $validated['sk_menkeu_date'] ?? null,
            'sttd_ojk_date'  => $validated['sttd_ojk_date'] ?? null,
            'klasifikasi'    => $validated['klasifikasi'] ?? null,
        ])->save();

        AuditLogger::record('profile.updated', 'Memperbarui biodata profil sendiri', $user);

        return redirect()->route('profile.show')->with('success', 'Biodata profil berhasil diperbarui.');
    }

    /**
     * Toggle preferensi tampilan (light/dark) — tersimpan di akun user
     * sendiri (bukan localStorage) supaya ikut terbawa lintas perangkat.
     * Dipanggil via fetch() dari tombol di sidebar; sengaja tanpa redirect,
     * cukup balas status baru supaya JS tinggal update class <html>.
     */
    public function toggleTheme(Request $request)
    {
        $user = Auth::user();
        $user->update(['dark_mode' => ! $user->dark_mode]);

        return response()->json(['dark_mode' => $user->dark_mode]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);
        }

        $user->password = $validated['password']; // ter-hash otomatis lewat cast 'hashed'
        $user->save();

        AuditLogger::record('profile.password_changed', 'Mengganti password sendiri', $user);

        return back()->with('success', 'Password berhasil diubah.');
    }
}
