<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Izin baru Batch 4 (idempotent — aman dijalankan sebelum/ tanpa re-seed
 * RolePermissionSeeder):
 *  - banks.manage       : CRUD master rekening bank. Default hanya Administrator
 *                         (Administrator selalu punya semua izin secara hardcode).
 *  - tax_invoice.manage : isi Nomor & Tanggal Faktur Pajak di proposal.
 *                         Diberikan ke Admin Keuangan (+ Administrator).
 */
return new class extends Migration
{
    private const PERMS = [
        ['key' => 'banks.manage',       'label' => 'Kelola Master Rekening Bank',        'group' => 'Pengaturan Sistem'],
        ['key' => 'tax_invoice.manage', 'label' => 'Isi Nomor & Tanggal Faktur Pajak',  'group' => 'Invoice & Pembayaran'],
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
        $taxPermId  = DB::table('permissions')->where('key', 'tax_invoice.manage')->value('id');

        if ($keuanganId && $taxPermId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $keuanganId, 'permission_id' => $taxPermId],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('key', array_column(self::PERMS, 'key'))->delete();
    }
};
