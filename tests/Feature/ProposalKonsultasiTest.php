<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jasa Konsultasi (2026-09-25, permintaan user): Kajian Kewajaran RAB, Studi
 * Kelayakan, dan Pengawasan Proyek. Alur produksinya sama dengan penilaian —
 * yang berbeda hanya isian form, istilah tahap review, dan isi dokumennya.
 */
class ProposalKonsultasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Client $klien;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->firstOrFail();
        $this->klien = Client::create(['client_name' => 'PT Uji Konsultasi', 'client_type' => 'Korporat', 'address' => 'Jl. Uji']);
    }

    /** Isian form yang minimal valid untuk proposal konsultasi. */
    private function data(array $timpa = []): array
    {
        return array_merge([
            'proposal_number'         => 'KONS/2026/001',
            'proposal_date'           => now()->toDateString(),
            'service_type'            => Project::SERVICE_KONSULTASI,
            'consulting_type'         => Project::CONSULTING_FS,
            'work_object_description' => 'Pekerjaan pembangunan Pabrik Pengolahan Tembakau yang diprakarsai oleh PT Uji Konsultasi yang berlokasi di Lamongan.',
            'instructing_client_id'   => $this->klien->id,
            'intended_user_ids'       => [$this->klien->id],
            'signed_by_user_id'       => $this->admin->id,
            'service_fee'             => 100_000_000,
            'report_style'            => Project::REPORT_LONG,
            'sla_draft_days'          => 7,
            'sla_final_days'          => 14,
            'payment_terms'           => '50,50',
            // Objek konsultasi: lokasi saja, tanpa kategori/hak/atas nama.
            'objects'                 => [['location' => 'Jl. Raya Mantup KM 16, Lamongan']],
        ], $timpa);
    }

    public function test_modal_pemilih_membuka_form_sesuai_jenis(): void
    {
        $this->actingAs($this->admin)
            ->get(route('proposals.create', ['layanan' => Project::SERVICE_KONSULTASI, 'jenis' => Project::CONSULTING_RAB]))
            ->assertOk()
            ->assertSee('Uraian Objek Pekerjaan')
            ->assertDontSee('Tujuan Penilaian');
    }

    public function test_jenis_pekerjaan_dipilih_di_dalam_form(): void
    {
        // Jenis pekerjaan tidak lagi dipilih di modal, jadi halaman Buat
        // Proposal Non-Penilaian terbuka tanpa parameter jenis.
        $this->actingAs($this->admin)
            ->get(route('proposals.create', ['layanan' => Project::SERVICE_KONSULTASI]))
            ->assertOk()
            ->assertSee('Jenis Pekerjaan')
            ->assertSee(Project::CONSULTING_RAB)
            ->assertSee(Project::CONSULTING_FS)
            ->assertSee(Project::CONSULTING_PENGAWASAN);
    }

    public function test_jenis_pekerjaan_tidak_bisa_diubah_setelah_dibuat(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->data());
        $proyek = Project::where('proposal_number', 'KONS/2026/001')->firstOrFail();
        $this->assertSame(Project::CONSULTING_FS, $proyek->consulting_type);

        // Kiriman yang mencoba mengganti jenis pekerjaan diabaikan.
        $this->actingAs($this->admin)
            ->put(route('proposals.update', $proyek), $this->data([
                'consulting_type' => Project::CONSULTING_PENGAWASAN,
            ]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(Project::CONSULTING_FS, $proyek->fresh()->consulting_type);
        $this->assertSame(Project::CONSULTING_FS, $proyek->fresh()->proposal_purpose);
    }

    public function test_form_penilaian_tetap_seperti_semula(): void
    {
        $this->actingAs($this->admin)
            ->get(route('proposals.create'))
            ->assertOk()
            ->assertSee('Tujuan Penilaian')
            ->assertSee('Identifikasi Objek Penilaian')
            ->assertDontSee('Uraian Objek Pekerjaan');
    }

    public function test_simpan_proposal_konsultasi_tanpa_data_khusus_penilaian(): void
    {
        $this->actingAs($this->admin)
            ->post(route('proposals.store'), $this->data())
            ->assertRedirect()->assertSessionHasNoErrors();

        $proyek = Project::where('proposal_number', 'KONS/2026/001')->firstOrFail();

        $this->assertTrue($proyek->isKonsultasi());
        $this->assertSame(Project::CONSULTING_FS, $proyek->consulting_type);
        // proposal_purpose diisi jenis konsultasinya supaya filter & export jalan.
        $this->assertSame(Project::CONSULTING_FS, $proyek->proposal_purpose);
        $this->assertSame('Studi Kelayakan', $proyek->service_label);

        $objek = $proyek->valuationObjects()->firstOrFail();
        $this->assertSame('Jl. Raya Mantup KM 16, Lamongan', $objek->location);
        $this->assertNull($objek->asset_category);
        $this->assertNull($objek->ownership_form);
    }

    public function test_uraian_objek_pekerjaan_wajib_diisi(): void
    {
        $this->actingAs($this->admin)
            ->post(route('proposals.store'), $this->data(['work_object_description' => '']))
            ->assertSessionHasErrors('work_object_description');
    }

    public function test_proposal_penilaian_tetap_mewajibkan_data_objek(): void
    {
        $this->actingAs($this->admin)
            ->post(route('proposals.store'), $this->data([
                'proposal_number'  => 'NILAI/2026/001',
                'service_type'     => Project::SERVICE_PENILAIAN,
                'consulting_type'  => null,
                'proposal_purpose' => Project::PURPOSE_JUAL_BELI,
                'objects'          => [['location' => 'Jl. Objek']],
            ]))
            ->assertSessionHasErrors(['objects.0.asset_category', 'objects.0.ownership_form', 'objects.0.owner_name']);
    }

    public function test_halaman_proyek_konsultasi_terbuka_tanpa_galat(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->data());
        $proyek = Project::where('proposal_number', 'KONS/2026/001')->firstOrFail();

        // Objeknya tanpa kategori/hak/atas nama — halaman detail, halaman
        // baca-saja, dan form edit tetap harus terbuka.
        $this->actingAs($this->admin)->get(route('proposals.show', $proyek))->assertOk();
        $this->actingAs($this->admin)->get(route('proposals.lengkap', $proyek))->assertOk();
        $this->actingAs($this->admin)->get(route('proposals.edit', $proyek))
            ->assertOk()
            ->assertSee('Uraian Objek Pekerjaan')
            ->assertDontSee('Tujuan Penilaian');
    }

    /** Dokumen .docx konsultasi memakai susunan bab masternya sendiri. */
    public function test_docx_konsultasi_memakai_bab_master(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->data([
            'consulting_type' => Project::CONSULTING_RAB,
            'letter_attn'     => 'Bapak Direktur Utama',
        ]));

        $proyek = Project::where('proposal_number', 'KONS/2026/001')->firstOrFail();
        $path   = \App\Services\ProposalDocxBuilder::for($proyek)->save();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $polos = strip_tags(str_replace('<', ' <', $xml));

        // Bab khas Kajian Kewajaran RAB hadir, bab khas penilaian tidak.
        foreach (['Pendahuluan', 'Bentuk Kepemilikan', 'Ruang Lingkup Laporan', 'Benturan Kepentingan'] as $bab) {
            $this->assertStringContainsString($bab, $polos, "Bab \"{$bab}\" tidak ada di dokumen.");
        }
        foreach (['Dasar Nilai', 'Tingkat Kedalaman Investigasi', 'Waktu Ekspos'] as $bab) {
            $this->assertStringNotContainsString($bab, $polos, "Bab penilaian \"{$bab}\" seharusnya tidak ikut.");
        }

        $this->assertStringContainsString('Up. : Bapak Direktur Utama', $polos);
        $this->assertStringContainsString('Biaya Jasa Konsultasi Analisis Kewajaran', $polos);
    }

    /** Studi Kelayakan membawa lampiran Kerangka Acuan Kerja. */
    public function test_studi_kelayakan_membawa_lampiran_kak(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->data());
        $proyek = Project::where('proposal_number', 'KONS/2026/001')->firstOrFail();

        $path = \App\Services\ProposalDocxBuilder::for($proyek)->save();
        $zip  = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $polos = strip_tags(str_replace('<', ' <', $xml));

        $this->assertStringContainsString('KERANGKA ACUAN KERJA', $polos);
        $this->assertStringContainsString('TERM OF REFERENCE', $polos);
        $this->assertStringContainsString('LAMPIRAN - 2', $polos);
    }

    public function test_istilah_alur_konsultasi_memakai_kata_summary(): void
    {
        $this->actingAs($this->admin)->post(route('proposals.store'), $this->data());
        $proyek = Project::where('proposal_number', 'KONS/2026/001')->firstOrFail();

        $this->assertSame('Submit Review Summary', $proyek->istilahAlur('Submit Review Nilai'));
        $this->assertSame('Summary Disetujui', $proyek->istilahAlur('Draft Resume Disetujui'));

        $penilaian = new Project(['service_type' => Project::SERVICE_PENILAIAN]);
        $this->assertSame('Submit Review Nilai', $penilaian->istilahAlur('Submit Review Nilai'));
    }
}
