<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol "Kosongkan Sampah" (2026-09-25, permintaan user): menghapus permanen
 * seluruh isi Sampah sekaligus. Hanya Administrator, dan wajib kata sandi.
 */
class KosongkanSampahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->firstOrFail();
        $this->admin->forceFill(['password' => 'rahasia-uji'])->save();
    }

    /** Satu klien dan satu proyek yang sudah masuk Sampah. */
    private function isiSampah(): void
    {
        $klien = Client::create(['client_name' => 'PT Buang Saja', 'client_type' => 'Korporat', 'address' => 'Jl. Uji']);

        $proyek = Project::create([
            'proposal_number' => 'SAMPAH/2026/001', 'proposal_date' => now()->toDateString(),
            'instructing_client_id' => $klien->id, 'signed_by_user_id' => $this->admin->id,
            'service_fee' => 1_000_000, 'fee_ppn_included' => true,
            'report_style' => Project::REPORT_LONG, 'sla_draft_days' => 5, 'sla_final_days' => 7,
            'proposal_purpose' => Project::PURPOSE_JUAL_BELI,
            'payment_scheme' => Project::PAYMENT_SCHEME_DP, 'status' => Project::STATUS_DRAFT,
            'asset_type' => 'Tanah', 'asset_address' => 'Jl. Objek',
        ]);

        $proyek->delete();
        $klien->delete();
    }

    public function test_administrator_dengan_kata_sandi_benar_mengosongkan_sampah(): void
    {
        $this->isiSampah();

        $this->actingAs($this->admin)
            ->delete(route('trash.purgeAll'), ['password' => 'rahasia-uji'])
            ->assertRedirect(route('trash.index'));

        $this->assertSame(0, Project::onlyTrashed()->count());
        $this->assertSame(0, Client::onlyTrashed()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'trash.purged']);
    }

    public function test_kata_sandi_salah_tidak_menghapus_apa_pun(): void
    {
        $this->isiSampah();

        $this->actingAs($this->admin)
            ->delete(route('trash.purgeAll'), ['password' => 'salah'])
            ->assertSessionHasErrors('password');

        $this->assertSame(1, Project::onlyTrashed()->count());
        $this->assertSame(1, Client::onlyTrashed()->count());
    }

    public function test_selain_administrator_ditolak(): void
    {
        $this->isiSampah();

        $produksi = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN_PRODUKSI))->firstOrFail();

        $this->actingAs($produksi)
            ->delete(route('trash.purgeAll'), ['password' => 'rahasia-uji'])
            ->assertForbidden();

        $this->assertSame(1, Project::onlyTrashed()->count());
    }

    public function test_data_yang_tidak_di_sampah_tidak_ikut_terhapus(): void
    {
        $this->isiSampah();
        $aktif = Client::create(['client_name' => 'PT Masih Aktif', 'client_type' => 'Korporat', 'address' => 'Jl. Aktif']);

        $this->actingAs($this->admin)
            ->delete(route('trash.purgeAll'), ['password' => 'rahasia-uji'])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['id' => $aktif->id, 'deleted_at' => null]);
    }
}
