<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur kerja inti aplikasi, dari proposal sampai buku dicetak.
 *
 * Tes ini menjaga lima langkah yang paling mahal kalau rusak diam-diam:
 * membuat proposal, menerbitkan invoice, menandai lunas, mengajukan review
 * nilai, dan menandai buku selesai dicetak.
 *
 * Memakai database MySQL terpisah (spr_db_test, lihat phpunit.xml) karena
 * aplikasi memakai SQL khusus MySQL untuk perhitungan SLA hari kerja.
 */
class AlurProyekTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Client $klien;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name'      => 'Administrator Uji',
            'email'     => 'admin.uji@example.test',
            'password'  => 'rahasia123',
            'role_id'   => Role::where('slug', Role::ADMINISTRATOR)->value('id'),
            'is_active' => true,
            'jabatan'   => User::JABATAN_PENANGGUNG_JAWAB,
        ]);

        $this->klien = Client::create([
            'client_name' => 'PT Klien Uji',
            'client_type' => 'Korporat',
            'address'     => 'Jl. Uji No. 1, Jakarta',
        ]);

        Bank::create([
            'bank_name'      => 'Bank Uji',
            'account_number' => '1234567890',
            'account_name'   => 'KJPP Uji',
            'is_default'     => true,
        ]);
    }

    /** Isian form proposal yang sah; bisa ditimpa per tes. */
    private function dataProposal(array $timpa = []): array
    {
        return array_merge([
            'proposal_number'       => '00123/2.0131-00/KJPPSPR-PRO/APP/IX/2026',
            'proposal_date'         => now()->toDateString(),
            'instructing_client_id' => $this->klien->id,
            'signed_by_user_id'     => $this->admin->id,
            'intended_user_ids'     => [$this->klien->id],
            'service_fee'           => 50_000_000,
            'fee_ppn_included'      => 1,
            'report_style'          => Project::REPORT_LONG,
            'sla_draft_days'        => 5,
            'sla_final_days'        => 7,
            'proposal_purpose'      => Project::PURPOSE_PENJAMINAN_UTANG,
            'payment_scheme'        => Project::PAYMENT_SCHEME_DP,
            'objects'               => [[
                'asset_category' => 'Real Properti - Tanah dan Bangunan',
                'location'       => 'Jl. Objek Uji No. 7, Bekasi',
                'ownership_form' => 'SHM',
                'owner_name'     => 'Budi Uji',
            ]],
        ], $timpa);
    }

    /** Proyek siap pakai tanpa lewat form, untuk tes langkah berikutnya. */
    private function buatProyek(array $timpa = []): Project
    {
        return Project::create(array_merge([
            'proposal_number'       => '00999/2.0131-00/KJPPSPR-PRO/APP/IX/2026',
            'proposal_date'         => now()->toDateString(),
            'instructing_client_id' => $this->klien->id,
            'signed_by_user_id'     => $this->admin->id,
            'service_fee'           => 20_000_000,
            'fee_ppn_included'      => true,
            'report_style'          => Project::REPORT_LONG,
            'sla_draft_days'        => 5,
            'sla_final_days'        => 7,
            'proposal_purpose'      => Project::PURPOSE_PENJAMINAN_UTANG,
            'payment_scheme'        => Project::PAYMENT_SCHEME_DP,
            'status'                => Project::STATUS_DRAFT,
            'asset_type'            => 'Tanah dan Bangunan',
            'asset_address'         => 'Jl. Objek Uji No. 7, Bekasi',
        ], $timpa));
    }

    public function test_membuat_proposal_menyimpan_proyek_dan_objeknya(): void
    {
        $respons = $this->actingAs($this->admin)
            ->post(route('proposals.store'), $this->dataProposal());

        $respons->assertRedirect();

        $proyek = Project::where('proposal_number', '00123/2.0131-00/KJPPSPR-PRO/APP/IX/2026')->first();

        $this->assertNotNull($proyek, 'Proposal tidak tersimpan.');
        $this->assertSame(Project::STATUS_DRAFT, $proyek->status);
        $this->assertSame(1, $proyek->valuationObjects()->count());
        $this->assertSame($this->klien->id, $proyek->intendedUsers()->first()->id);
        // PPN sudah termasuk, jadi total = nilai yang diinput.
        $this->assertEqualsWithDelta(50_000_000, $proyek->total_fee, 0.01);
    }

    public function test_edit_proposal_menyimpan_barcode_tanda_tangan(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->dataProposal());
        $proyek = Project::first();

        $this->actingAs($this->admin)
            ->put(route('proposals.update', $proyek), $this->dataProposal([
                'use_signature_barcode' => 1,
                'use_stamp'             => 1,
                'signature_barcode'     => \Illuminate\Http\UploadedFile::fake()->image('ttd.png', 370, 370),
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $proyek->refresh();
        $this->assertTrue($proyek->use_signature_barcode);
        $this->assertTrue($proyek->use_stamp);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($proyek->signature_barcode);
    }

    public function test_nomor_proposal_tidak_boleh_kembar(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->dataProposal());

        $this->actingAs($this->admin)
            ->post(route('proposals.store'), $this->dataProposal())
            ->assertSessionHasErrors('proposal_number');

        $this->assertSame(1, Project::count());
    }

    public function test_menerbitkan_invoice_memindahkan_status_ke_dp_invoicing(): void
    {
        $proyek = $this->buatProyek();

        $this->actingAs($this->admin)
            ->post(route('invoices.store', $proyek), [
                'amount'           => 10_000_000,
                'term_description' => 'Termin 1',
            ])
            ->assertRedirect();

        $proyek->refresh();
        $invoice = $proyek->invoices()->first();

        $this->assertNotNull($invoice, 'Invoice tidak terbit.');
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(Project::STATUS_DP_INVOICING, $proyek->status);
        // remaining_balance = yang belum DIBAYAR; invoice baru terbit, belum lunas.
        $this->assertEqualsWithDelta(20_000_000, $proyek->remaining_balance, 0.01);
        $this->assertEqualsWithDelta(10_000_000, $proyek->invoices()->sum('amount'), 0.01);
    }

    public function test_invoice_tidak_boleh_melebihi_sisa_tagihan(): void
    {
        $proyek = $this->buatProyek();

        $this->actingAs($this->admin)
            ->post(route('invoices.store', $proyek), ['amount' => 99_000_000])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, $proyek->invoices()->count());
    }

    public function test_invoice_ditolak_bila_seluruh_kontrak_sudah_ditagihkan(): void
    {
        $proyek = $this->buatProyek();

        // Invoice penuh, belum dibayar: Sisa Tagihan 0, Sisa Pelunasan masih penuh.
        $this->actingAs($this->admin)->post(route('invoices.store', $proyek), ['amount' => 20_000_000]);

        $this->actingAs($this->admin)
            ->post(route('invoices.store', $proyek), ['amount' => 1_000_000])
            ->assertSessionHas('error');

        $proyek->refresh();
        $this->assertSame(1, $proyek->invoices()->count());
        $this->assertEqualsWithDelta(0, $proyek->uninvoiced_balance, 0.01);
        $this->assertEqualsWithDelta(20_000_000, $proyek->remaining_balance, 0.01);
    }

    public function test_menandai_invoice_lunas_menjalankan_pekerjaan(): void
    {
        $proyek = $this->buatProyek();

        $this->actingAs($this->admin)->post(route('invoices.store', $proyek), ['amount' => 20_000_000]);
        $invoice = $proyek->invoices()->first();

        $this->actingAs($this->admin)
            ->post(route('invoices.markAsPaid', $invoice), ['payment_date' => now()->toDateString()])
            ->assertRedirect();

        $proyek->refresh();
        $invoice->refresh();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(Project::STATUS_IN_PROGRESS, $proyek->status, 'DP lunas seharusnya memulai pekerjaan.');
        $this->assertTrue($proyek->is_fully_paid);
    }

    public function test_submit_review_nilai_menyimpan_catatan_untuk_reviewer(): void
    {
        $proyek = $this->buatProyek([
            'status'                => Project::STATUS_IN_PROGRESS,
            'survey_date'           => now()->subDays(3)->toDateString(),
            'assigned_appraiser_id' => $this->admin->id,
            'assigned_appraiser'    => $this->admin->name,
        ]);

        $this->actingAs($this->admin)
            ->post(route('projects.workflow', [$proyek, 'submit_value']), ['note' => 'Nilai pasar mengacu data pembanding 3 lokasi.'])
            ->assertRedirect();

        $proyek->refresh();

        $this->assertSame(Project::REVIEW_SUBMITTED, $proyek->review_status);
        $this->assertNotNull($proyek->review_submitted_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'review.submitted',
            'note'   => 'Nilai pasar mengacu data pembanding 3 lokasi.',
        ]);
    }

    public function test_menandai_buku_dicetak_menyelesaikan_proyek(): void
    {
        $proyek = $this->buatProyek([
            'status'                => Project::STATUS_IN_PROGRESS,
            'survey_date'           => now()->subDays(10)->toDateString(),
            'assigned_appraiser_id' => $this->admin->id,
            'assigned_appraiser'    => $this->admin->name,
            'review_status'         => Project::STAGE_DRAFT_REVIEWED,
            'final_report_number'   => '00045/LAP/KJPPSPR/IX/2026',
        ]);

        $this->actingAs($this->admin)
            ->post(route('projects.workflow', [$proyek, 'mark_printed']))
            ->assertRedirect();

        $proyek->refresh();

        $this->assertSame(Project::STAGE_PRINTED, $proyek->review_status);
        $this->assertSame(Project::STATUS_SELESAI, $proyek->status);
        $this->assertNotNull($proyek->printed_at);
    }

    public function test_buku_tidak_bisa_dicetak_tanpa_nomor_laporan_final(): void
    {
        $proyek = $this->buatProyek([
            'status'                => Project::STATUS_IN_PROGRESS,
            'survey_date'           => now()->subDays(10)->toDateString(),
            'assigned_appraiser_id' => $this->admin->id,
            'assigned_appraiser'    => $this->admin->name,
            'review_status'         => Project::STAGE_DRAFT_REVIEWED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('projects.workflow', [$proyek, 'mark_printed']))
            ->assertRedirect()
            ->assertSessionHas('error');

        $proyek->refresh();
        $this->assertSame(Project::STATUS_IN_PROGRESS, $proyek->status);
        $this->assertSame(Project::STAGE_DRAFT_REVIEWED, $proyek->review_status, 'Tahap tidak boleh maju tanpa Nomor Laporan Final.');
    }
}
