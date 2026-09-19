<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Matrix: baris = permission (dikelompokkan per modul), kolom = role.
     * Administrator TIDAK ditampilkan sebagai kolom yang bisa diedit
     * (selalu dianggap centang semua) — mencegah admin tidak sengaja
     * mencabut izinnya sendiri sampai terkunci dari sistem.
     */
    public function index()
    {
        $roles = Role::where('slug', '!=', Role::ADMINISTRATOR)
            ->with('permissions')
            ->orderBy('id')
            ->get();

        $permissionsByGroup = Permission::orderBy('id')->get()->groupBy('group');

        return view('roles.index', compact('roles', 'permissionsByGroup'));
    }

    /**
     * Menyimpan SELURUH matrix sekaligus dalam 1 submit. Struktur input
     * dari form: permissions[{role_id}][] = [id_permission, id_permission, ...]
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'array',
            'permissions.*.*' => 'exists:permissions,id',
        ]);

        $roles = Role::where('slug', '!=', Role::ADMINISTRATOR)->get();

        foreach ($roles as $role) {
            $permissionIds = $validated['permissions'][$role->id] ?? [];
            $role->permissions()->sync($permissionIds);
        }
        \App\Helpers\AuditLogger::record('roles.permissions_updated', 'Memperbarui matrix hak akses (Role Management)');
        return back()->with('success', 'Hak akses per role berhasil diperbarui.');
    }
}
