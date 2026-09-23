<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DeliveryReceipt;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tanda Terima Pengiriman Buku (2026-09-23). */
class TandaTerimaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Project $proyek;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Administrator Uji', 'email' => 'admin.tt@example.test', 'password' => 'rahasia123',
            'role_id' => Role::where('slug', Role::ADMINISTRATOR)->value('id'), 'is_active' => true,
        ]);
        $klien = Client::create(['client_name' => 'PT Klien Uji', 'client_type' => 'Korporat', 'address' => "Jl. Uji No. 1\nJakarta"]);

        $this->proyek = Project::create([
            'proposal_number' => '00888/UJI/IX/2026', 'proposal_date' => now()->toDateString(),
            'instructing_client_id' => $klien->id, 'signed_by_user_id' => $this->admin->id,
            'service_fee' => 10_000_000, 'fee_ppn_included' => true, 'report_style' => Project::REPORT_LONG,
            'sla_draft_days' => 5, 'sla_final_days' => 7, 'proposal_purpose' => Project::PURPOSE_PENJAMINAN_UTANG,
            'payment_scheme' => Project::PAYMENT_SCHEME_DP, 'status' => Project::STATUS_PENGIRIMAN,
            'review_status' => Project::STAGE_SIGNED, 'asset_type' => 'Tanah', 'asset_address' => 'Jl. Objek',
            'final_report_number' => '00045/LAP/KJPPSPR/IX/2026',
        ]);
    }

    private function data(array $timpa = []): array
    {
        return array_merge([
            'delivery_date' => now()->toDateString(),
            'recipient_up'  => 'Bpk. Budi',
            'note'          => 'Laporan Penilaian an PT Klien Uji',
            'documents'     => ['laporan' => 2, 'invoice' => 2, 'kwitansi' => 2, 'faktur_pajak' => 0],
        ], $timpa);
    }

    public function test_tanda_terima_menyelesaikan_proyek_dan_nomor_urut_berjalan(): void
    {
        $this->actingAs($this->admin)
            ->post(route('receipts.store', $this->proyek), $this->data())
            ->assertRedirect()->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::first();
        $this->assertSame('1/' . now()->format('m/Y'), $receipt->number);
        // Faktur pajak qty 0 tidak ikut tercatat.
        $this->assertSame(['laporan', 'invoice', 'kwitansi'], array_column($receipt->documents, 'key'));
        $this->assertCount(3, $receipt->documentRows());

        // Belum lunas -> "Selesai - Belum Lunas".
        $this->proyek->refresh();
        $this->assertSame(Project::STATUS_SELESAI_BELUM_LUNAS, $this->proyek->status);
        $this->assertSame(Project::STAGE_DELIVERED, $this->proyek->review_status);
        $this->assertNotNull($this->proyek->delivered_at);

        // Tanda terima kedua: nomor lanjut, status tidak berubah lagi.
        $this->actingAs($this->admin)->post(route('receipts.store', $this->proyek), $this->data())->assertRedirect();
        $this->assertSame('2/' . now()->format('m/Y'), DeliveryReceipt::latest('id')->first()->number);
        $this->assertSame(Project::STATUS_SELESAI_BELUM_LUNAS, $this->proyek->fresh()->status);
    }

    public function test_tanda_terima_wajib_punya_dokumen(): void
    {
        $this->actingAs($this->admin)
            ->post(route('receipts.store', $this->proyek), $this->data(['documents' => ['laporan' => 0]]))
            ->assertSessionHasErrors('documents');

        $this->assertSame(0, DeliveryReceipt::count());
    }

    public function test_unduh_pdf_dan_word_memakai_nama_file_bernomor(): void
    {
        $this->actingAs($this->admin)->post(route('receipts.store', $this->proyek), $this->data());
        $receipt = DeliveryReceipt::first();

        $this->assertSame('1 - Tanda Terima_PT Klien Uji', $receipt->fileName());

        $this->actingAs($this->admin)->get(route('receipts.pdf', [$this->proyek, $receipt]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('receipts.word', [$this->proyek, $receipt]))->assertOk();
    }
}
