<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Izin baru (idempotent — aman dijalankan sebelum/tanpa re-seed
 * RolePermissionSeeder):
 *  - assignment_letter.manage : isi Nomor/Tanggal/Barcode Surat Tugas +
 *    pilih Reviewer. Sama seperti tax_invoice.manage: default hanya Admin
 *    Keuangan (Administrator otomatis punya semua izin secara hardcode).
 */
return new class extends Migration
{
    private const PERMS = [
        ['key' => 'assignment_letter.manage', 'label' => 'Isi Nomor/Tanggal/Barcode Surat Tugas & Pilih Reviewer', 'group' => 'Survei Lapangan'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMS as $p) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $p['key']],
                ['label' => $p['label'], 'group' => $p['group'], 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $keuanganId = DB::table('roles')->where('slug', Role::ADMIN_KEUANGAN)->value('id');
        $permId     = DB::table('permissions')->where('key', 'assignment_letter.manage')->value('id');

        if ($keuanganId && $permId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $keuanganId, 'permission_id' => $permId],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('key', array_column(self::PERMS, 'key'))->delete();
    }
};
