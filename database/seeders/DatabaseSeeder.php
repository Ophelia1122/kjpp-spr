<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan WAJIB: ClientSeeder dulu, karena ProjectSeeder butuh
        // client_id (instructing_client_id & intended_users) yang valid.
        $this->call([
            ClientSeeder::class,
            ProjectSeeder::class,
        ]);
    }
}
