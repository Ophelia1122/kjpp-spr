<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan WAJIB:
        // 1. RolePermissionSeeder dulu — User butuh role_id yang valid.
        // 2. UserSeeder — supaya langsung bisa login & coba tiap role.
        // 3. ClientSeeder — Project butuh client_id yang valid.
        // 4. ProjectSeeder — paling akhir, bergantung ke Client.
        $this->call([
            RolePermissionSeeder::class,
            BankSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            ProjectSeeder::class,
        ]);
    }
}
