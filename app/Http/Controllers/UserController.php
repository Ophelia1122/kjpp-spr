<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role')->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('email', 'like', '%' . $request->q . '%');
        }

        $users = $query->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('id')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id'  => 'required|exists:roles,id',
        ] + $this->biodataRules());

        $user = User::create($validated); // password otomatis ter-hash lewat cast 'hashed' di Model
        \App\Helpers\AuditLogger::record('user.created', "Menambahkan pengguna baru \"{$user->name}\" dengan role {$user->role?->name}", $user);
        return redirect()
            ->route('users.index')
            ->with('success', "Pengguna \"{$user->name}\" berhasil ditambahkan.");
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('id')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role_id'  => 'required|exists:roles,id',
            'is_active' => 'nullable|boolean',
        ] + $this->biodataRules());

        // GUARD: user tidak boleh menonaktifkan akunnya sendiri (bisa
        // bikin dia ter-logout paksa di tengah sesi kerja sendiri tanpa
        // ada admin lain yang sadar).
        if ($user->id === auth()->id() && !$request->boolean('is_active')) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->name    = $validated['name'];
        $user->email   = $validated['email'];
        $user->role_id = $validated['role_id'];
        $user->is_active = $request->boolean('is_active');

        $user->fill([
            'jabatan'         => $validated['jabatan'] ?? null,
            'partner_status'  => $validated['partner_status'] ?? null,
            'mappi_no'        => $validated['mappi_no'] ?? null,
            'rmk_no'          => $validated['rmk_no'] ?? null,
            'izin_menkeu_no'  => $validated['izin_menkeu_no'] ?? null,
            'sk_menkeu_no'    => $validated['sk_menkeu_no'] ?? null,
            'sttd_ojk_no'     => $validated['sttd_ojk_no'] ?? null,
            'ojk_kep_no'      => $validated['ojk_kep_no'] ?? null,
            'klasifikasi'     => $validated['klasifikasi'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $user->password = $validated['password']; // ter-hash otomatis
        }

        $user->save();
        \App\Helpers\AuditLogger::record('user.updated', "Mengubah data pengguna \"{$user->name}\"", $user);
        return redirect()
            ->route('users.index')
            ->with('success', "Data pengguna \"{$user->name}\" berhasil diperbarui.");
    }

    /**
     * GUARD: tidak boleh hapus akun sendiri, dan tidak boleh hapus
     * Administrator terakhir yang tersisa (supaya sistem tidak pernah
     * kehilangan akses penuh sama sekali).
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->isAdministrator()) {
            $adminCount = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Tidak dapat menghapus Administrator terakhir yang tersisa di sistem.');
            }
        }

        $userName = $user->name;
        \App\Helpers\AuditLogger::record('user.deleted', "Menghapus pengguna \"{$userName}\"", $user);
        $user->delete();

        return back()->with('success', "Pengguna \"{$userName}\" berhasil dihapus.");
    }

    /**
     * Aturan validasi biodata profesi — dipakai bersama store() & update().
     * Semua opsional: hanya relevan untuk Penilai / Penanggung Jawab.
     */
    private function biodataRules(): array
    {
        return [
            'jabatan'         => ['nullable', Rule::in(User::JABATAN_OPTIONS)],
            'partner_status'  => 'nullable|string|max:255',
            'mappi_no'        => 'nullable|string|max:255',
            'rmk_no'          => 'nullable|string|max:255',
            'izin_menkeu_no'  => 'nullable|string|max:255',
            'sk_menkeu_no'    => 'nullable|string|max:255',
            'sttd_ojk_no'     => 'nullable|string|max:255',
            'ojk_kep_no'      => 'nullable|string|max:255',
            'klasifikasi'     => 'nullable|string|max:255',
        ];
    }
}
