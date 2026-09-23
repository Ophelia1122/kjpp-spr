<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Login memakai username (2026-09-23, feedback user). */
class LoginUsernameTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(array $timpa = []): User
    {
        $this->seed(RolePermissionSeeder::class);

        return User::create(array_merge([
            'name' => 'Budi Uji', 'username' => 'budi.uji', 'email' => 'budi@example.test',
            'password' => 'rahasia123', 'role_id' => Role::where('slug', Role::ADMINISTRATOR)->value('id'),
            'is_active' => true,
        ], $timpa));
    }

    public function test_login_dengan_username_berhasil(): void
    {
        $user = $this->buatUser();

        $this->post(route('login.attempt'), ['username' => 'BUDI.UJI', 'password' => 'rahasia123'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_dengan_email_ditolak(): void
    {
        $this->buatUser();

        $this->post(route('login.attempt'), ['username' => 'budi@example.test', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_diberi_pesan_khusus(): void
    {
        $this->buatUser(['is_active' => false]);

        $this->post(route('login.attempt'), ['username' => 'budi.uji', 'password' => 'rahasia123'])
            ->assertSessionHasErrors(['username' => 'Akun ini telah dinonaktifkan. Hubungi Administrator.']);

        $this->assertGuest();
    }

    public function test_username_wajib_unik_dan_format_benar(): void
    {
        $admin = $this->buatUser();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'User Dua', 'username' => 'budi.uji', 'email' => 'dua@example.test',
            'password' => 'rahasia123', 'role_id' => $admin->role_id,
        ])->assertSessionHasErrors('username');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'User Tiga', 'username' => 'Budi Spasi', 'email' => 'tiga@example.test',
            'password' => 'rahasia123', 'role_id' => $admin->role_id,
        ])->assertSessionHasErrors('username');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'User Empat', 'username' => 'user.empat', 'email' => 'empat@example.test',
            'password' => 'rahasia123', 'role_id' => $admin->role_id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['username' => 'user.empat']);
    }
}
