<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nego biaya jasa (2026-09-25, feedback user): perubahan biaya pada proposal
 * wajib beralasan, tercatat di Riwayat Proyek, dan nilai penawaran awal tidak
 * ikut berubah.
 */
class NegoBiayaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Project $proyek;
    private Client $klien;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->firstOrFail();
        $this->klien = Client::create(['client_name' => 'PT Klien Nego', 'client_type' => 'Korporat', 'address' => 'Jl. Uji']);

        $this->proyek = Project::create([
            'proposal_number' => 'NEGO/2026/001', 'proposal_date' => now()->toDateString(),
            'instructing_client_id' => $this->klien->id, 'signed_by_user_id' => $this->admin->id,
            'service_fee' => 15_000_000, 'initial_service_fee' => 15_000_000,
            'fee_ppn_included' => true, 'report_style' => Project::REPORT_LONG,
            'sla_draft_days' => 5, 'sla_final_days' => 7,
            'proposal_purpose' => Project::PURPOSE_PENJAMINAN_UTANG,
            'payment_scheme' => Project::PAYMENT_SCHEME_DP, 'status' => Project::STATUS_DRAFT,
            'asset_type' => 'Tanah', 'asset_address' => 'Jl. Objek',
        ]);
    }

    /** Isian form edit yang minimal valid. */
    private function data(array $timpa = []): array
    {
        return array_merge([
            'proposal_number'   => $this->proyek->proposal_number,
            'proposal_date'     => now()->toDateString(),
            'instructing_client_id' => $this->klien->id,
            'intended_user_ids' => [$this->klien->id],
            'signed_by_user_id' => $this->admin->id,
            'service_fee'       => 15_000_000,
            'report_style'      => Project::REPORT_LONG,
            'sla_draft_days'    => 5,
            'sla_final_days'    => 7,
            'proposal_purpose'  => Project::PURPOSE_PENJAMINAN_UTANG,
            'payment_terms'     => '50,50',
            'objects'           => [[
                'asset_category' => 'Real Properti - Tanah',
                'location'       => 'Jl. Objek No. 1',
                'ownership_form' => 'SHM',
                'owner_name'     => 'PT Klien Nego',
            ]],
        ], $timpa);
    }

    public function test_ubah_biaya_tanpa_alasan_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->put(route('proposals.update', $this->proyek), $this->data(['service_fee' => 12_500_000]))
            ->assertSessionHasErrors('fee_change_reason');

        $this->assertSame(15_000_000.0, (float) $this->proyek->fresh()->service_fee);
    }

    public function test_nego_tercatat_di_riwayat_dan_penawaran_awal_tetap(): void
    {
        $this->actingAs($this->admin)
            ->put(route('proposals.update', $this->proyek), $this->data([
                'service_fee'       => 12_500_000,
                'fee_change_reason' => 'Hasil nego dengan klien 25 September 2026',
            ]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->proyek->refresh();

        $this->assertSame(12_500_000.0, (float) $this->proyek->service_fee);
        $this->assertSame(15_000_000.0, (float) $this->proyek->initial_service_fee);
        $this->assertTrue($this->proyek->feeDinego());
        $this->assertSame(2_500_000.0, $this->proyek->fee_nego_selisih);

        $log = AuditLog::where('action', 'proposal.fee_changed')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Rp 15.000.000', $log->description);
        $this->assertStringContainsString('Rp 12.500.000', $log->description);
        $this->assertSame('Hasil nego dengan klien 25 September 2026', $log->note);
    }

    public function test_simpan_tanpa_mengubah_biaya_tidak_butuh_alasan(): void
    {
        $this->actingAs($this->admin)
            ->put(route('proposals.update', $this->proyek), $this->data())
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(0, AuditLog::where('action', 'proposal.fee_changed')->count());
    }
}
