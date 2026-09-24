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

    public function test_tanggal_survei_per_objek_menentukan_sla_dan_tanggal_penilaian(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->dataProposal([
            'objects' => [
                ['asset_category' => 'Real Properti - Tanah dan Bangunan', 'location' => 'Objek A', 'ownership_form' => 'SHM', 'owner_name' => 'A'],
                ['asset_category' => 'Real Properti - Tanah dan Bangunan', 'location' => 'Objek B', 'ownership_form' => 'SHM', 'owner_name' => 'B'],
            ],
        ]));
        $proyek = Project::with('valuationObjects')->first();
        $proyek->update(['status' => Project::STATUS_IN_PROGRESS]);
        [$a, $b] = $proyek->valuationObjects->all();

        $this->actingAs($this->admin)
            ->post(route('projects.inputSurveyData', $proyek), [
                'appraiser_ids' => [$this->admin->id],
                'surveys' => [
                    // Objek A: 2 hari; objek B: 1 hari (selesai kosong).
                    $a->id => ['start' => '2026-09-07', 'end' => '2026-09-08'],
                    $b->id => ['start' => '2026-09-09', 'end' => ''],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $proyek->refresh()->load('valuationObjects');
        $this->assertSame('2026-09-09', $proyek->survey_date->toDateString(), 'Tanggal survei proyek = tanggal terakhir.');
        $this->assertSame('2026-09-08', $proyek->valuationObjects->firstWhere('id', $a->id)->survey_end_date->toDateString());
        // Tanggal Penilaian kosong = ikut tanggal survei terakhir.
        $this->assertSame('2026-09-09', $proyek->valuation_date->toDateString());
        // SLA Draft mulai H+1: 9 Sep + 1 + 5 hari kerja = 17 Sep 2026.
        $this->assertSame('2026-09-17', $proyek->estimated_completion_date->toDateString());
    }

    public function test_tanggal_survei_selesai_tidak_boleh_sebelum_mulai(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->dataProposal());
        $proyek = Project::with('valuationObjects')->first();
        $proyek->update(['status' => Project::STATUS_IN_PROGRESS]);

        $this->actingAs($this->admin)
            ->post(route('projects.inputSurveyData', $proyek), [
                'appraiser_ids' => [$this->admin->id],
                'surveys' => [$proyek->valuationObjects->first()->id => ['start' => '2026-09-10', 'end' => '2026-09-09']],
            ])
            ->assertSessionHasErrors();
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

    public function test_draft_resume_dirilis_banding_lalu_disetujui(): void
    {
        $proyek = $this->buatProyek([
            'status'                => Project::STATUS_IN_PROGRESS,
            'survey_date'           => now()->subDays(3)->toDateString(),
            'assigned_appraiser_id' => $this->admin->id,
            'assigned_appraiser'    => $this->admin->name,
            'review_status'         => Project::REVIEW_SUBMITTED,
        ]);

        $this->actingAs($this->admin)->post(route('projects.workflow', [$proyek, 'release_resume']))->assertRedirect();
        $this->assertSame(Project::REVIEW_RELEASED, $proyek->fresh()->review_status);

        // Banding: catatan wajib, tahap tidak berubah, tercatat di riwayat.
        $this->actingAs($this->admin)
            ->post(route('projects.workflow', [$proyek, 'appeal_resume']), ['note' => ''])
            ->assertSessionHasErrors('note');
        $this->actingAs($this->admin)
            ->post(route('projects.workflow', [$proyek, 'appeal_resume']), ['note' => 'Klien minta nilai pembanding lain.'])
            ->assertRedirect();
        $this->assertSame(Project::REVIEW_RELEASED, $proyek->fresh()->review_status);
        $this->assertNull($proyek->fresh()->review_approved_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'review.resume_appealed', 'note' => 'Klien minta nilai pembanding lain.']);

        // Disetujui: SLA Final mulai berjalan.
        $this->actingAs($this->admin)->post(route('projects.workflow', [$proyek, 'approve_value']))->assertRedirect();
        $proyek->refresh();
        $this->assertSame(Project::REVIEW_APPROVED, $proyek->review_status);
        $this->assertNotNull($proyek->review_approved_at);
    }

    public function test_admin_produksi_boleh_menyetujui_atau_banding_tetapi_tidak_mereview_nilai(): void
    {
        $adminProduksi = User::create([
            'name'      => 'Admin Produksi Uji',
            'email'     => 'produksi.uji@example.test',
            'password'  => 'rahasia123',
            'role_id'   => Role::where('slug', Role::ADMIN_PRODUKSI)->value('id'),
            'is_active' => true,
        ]);
        $proyek = $this->buatProyek([
            'status'                => Project::STATUS_IN_PROGRESS,
            'survey_date'           => now()->subDays(3)->toDateString(),
            'assigned_appraiser_id' => $this->admin->id,
            'assigned_appraiser'    => $this->admin->name,
            'review_status'         => Project::REVIEW_SUBMITTED,
        ]);

        // Me-review nilai adalah hak Reviewer: Admin Produksi tidak boleh
        // merilis Draft Resume maupun mengembalikan nilai ke Surveyor
        // (2026-09-24, feedback user).
        $this->actingAs($adminProduksi)->post(route('projects.workflow', [$proyek, 'release_resume']))->assertForbidden();
        $this->actingAs($adminProduksi)
            ->post(route('projects.workflow', [$proyek, 'return_value']), ['note' => 'Revisi.'])
            ->assertForbidden();

        // Setelah Draft Resume dirilis, Admin Produksi boleh menyetujui MAUPUN
        // mencatat banding.
        $proyek->update(['review_status' => Project::REVIEW_RELEASED]);
        $this->actingAs($adminProduksi)
            ->post(route('projects.workflow', [$proyek, 'appeal_resume']), ['note' => 'Banding.'])
            ->assertRedirect();
        $this->assertSame(Project::REVIEW_RELEASED, $proyek->fresh()->review_status);

        $this->actingAs($adminProduksi)->post(route('projects.workflow', [$proyek, 'approve_value']))->assertRedirect();
        $this->assertSame(Project::REVIEW_APPROVED, $proyek->fresh()->review_status);
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
            'status'                => Project::STATUS_FINALISASI,
        ]);

        $this->actingAs($this->admin)
            ->post(route('projects.workflow', [$proyek, 'mark_printed']))
            ->assertRedirect();

        $proyek->refresh();

        // Buku dicetak -> proses tanda tangan, lalu proses pengiriman buku.
        $this->assertSame(Project::STAGE_PRINTED, $proyek->review_status);
        $this->assertSame(Project::STATUS_TANDA_TANGAN, $proyek->status);
        $this->assertNotNull($proyek->printed_at);

        $this->actingAs($this->admin)->post(route('projects.workflow', [$proyek, 'mark_signed']))->assertRedirect();
        $proyek->refresh();
        $this->assertSame(Project::STAGE_SIGNED, $proyek->review_status);
        $this->assertSame(Project::STATUS_PENGIRIMAN, $proyek->status);
        $this->assertNotNull($proyek->signed_at);
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
