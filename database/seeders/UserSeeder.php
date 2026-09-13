<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * 1 akun testing per role, supaya Anda bisa langsung login-coba
     * bagaimana tampilan & akses berbeda antar role tanpa perlu bikin
     * user manual dulu lewat UI.
     *
     * ⚠️ GANTI SEMUA PASSWORD INI SEBELUM DEPLOY KE PRODUKSI.
     */
    public function run(): void
    {
        $roles = Role::all()->keyBy('slug');

        User::updateOrCreate(
            ['email' => 'admin@kjpp-spr.co.id'],
            [
                'name'     => 'Budi Prasodjo (Administrator)',
                'password' => 'password123',
                'role_id'  => $roles[Role::ADMINISTRATOR]->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'produksi@kjpp-spr.co.id'],
            [
                'name'     => 'Rina (Admin Produksi)',
                'password' => 'password123',
                'role_id'  => $roles[Role::ADMIN_PRODUKSI]->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'keuangan@kjpp-spr.co.id'],
            [
                'name'     => 'Dewi (General Admin)',
                'password' => 'password123',
                'role_id'  => $roles[Role::ADMIN_KEUANGAN]->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'surveyor@kjpp-spr.co.id'],
            [
                'name'     => 'Rudi Hartono (Surveyor)',
                'password' => 'password123',
                'role_id'  => $roles[Role::SURVEYOR]->id,
                'is_active' => true,
            ]
        );
    }
}
