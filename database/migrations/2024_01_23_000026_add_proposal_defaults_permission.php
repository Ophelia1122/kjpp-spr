<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Izin baru "proposal_defaults.manage" untuk menu Pengaturan Sistem >
 * Teks Baku Proposal (2026-09-25, permintaan user). Administrator selalu
 * boleh (hasPermission() hardcode true), General Admin diberi izin ini
 * lewat pivot. Role lain tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::updateOrCreate(
            ['key' => 'proposal_defaults.manage'],
            ['label' => 'Kelola Teks Baku Proposal per Tujuan Penilaian', 'group' => 'Pengaturan Sistem'],
        );

        $generalAdmin = Role::where('slug', Role::ADMIN_KEUANGAN)->first();

        if ($generalAdmin) {
            $generalAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        Permission::where('key', 'proposal_defaults.manage')->delete();
    }
};
