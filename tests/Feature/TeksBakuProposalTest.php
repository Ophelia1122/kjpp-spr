<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProposalSectionDefault;
use App\Models\Role;
use App\Models\User;
use App\Services\ProposalDocxBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Teks baku proposal per tujuan penilaian (2026-09-25, permintaan user):
 * Administrator/General Admin mengubah klausul dari menu Pengaturan Sistem,
 * dan teks itu dipakai proposal dengan tujuan penilaian yang sama.
 */
class TeksBakuProposalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Client $klien;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->firstOrFail();
        $this->klien = Client::create(['client_name' => 'PT Klien Baku', 'client_type' => 'Korporat', 'address' => 'Jl. Uji No. 1']);
    }

    private function proyek(string $purpose): Project
    {
        $project = Project::create([
            'proposal_number' => 'BAKU/2026/' . substr(md5($purpose), 0, 4),
            'proposal_date' => now()->toDateString(),
            'instructing_client_id' => $this->klien->id, 'signed_by_user_id' => $this->admin->id,
            'service_fee' => 10_000_000, 'fee_ppn_included' => true,
            'report_style' => Project::REPORT_LONG,
            'sla_draft_days' => 5, 'sla_final_days' => 7,
            'proposal_purpose' => $purpose,
            'payment_scheme' => Project::PAYMENT_SCHEME_DP, 'status' => Project::STATUS_DRAFT,
            'asset_type' => 'Tanah', 'asset_address' => 'Jl. Objek',
        ]);

        $project->intendedUsers()->sync([$this->klien->id]);

        return $project->fresh();
    }

    public function test_halaman_pengaturan_terbuka_dan_menampilkan_bab(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.proposalDefaults.index', ['tujuan' => Project::PURPOSE_LELANG]))
            ->assertOk()
            ->assertSee('Teks Baku Proposal')
            // Bab khusus Lelang hanya muncul di tab Lelang.
            ->assertSee('Waktu Ekspos (Exposure Time)');

        $this->actingAs($this->admin)
            ->get(route('settings.proposalDefaults.index'))
            ->assertOk()
            ->assertDontSee('Waktu Ekspos (Exposure Time)');
    }

    public function test_surveyor_tidak_boleh_membuka_pengaturan(): void
    {
        $surveyor = User::whereHas('role', fn ($q) => $q->where('slug', Role::SURVEYOR))->firstOrFail();

        $this->actingAs($surveyor)
            ->get(route('settings.proposalDefaults.index'))
            ->assertForbidden();
    }

    public function test_general_admin_boleh_menyimpan_teks_baku(): void
    {
        $generalAdmin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN_KEUANGAN))->firstOrFail();

        $this->actingAs($generalAdmin)
            ->put(route('settings.proposalDefaults.update', 'kerahasiaan'), [
                'tujuan' => Project::PURPOSE_LELANG,
                'body'   => 'Klausul kerahasiaan versi lelang.',
            ])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('proposal_section_defaults', [
            'proposal_purpose' => Project::PURPOSE_LELANG,
            'section_key'      => 'kerahasiaan',
            'body'             => 'Klausul kerahasiaan versi lelang.',
        ]);
    }

    public function test_teks_baku_hanya_berlaku_untuk_tujuan_yang_sama(): void
    {
        ProposalSectionDefault::create([
            'proposal_purpose' => Project::PURPOSE_LELANG,
            'section_key'      => 'kerahasiaan',
            'body'             => 'Klausul kerahasiaan khusus Lelang.',
        ]);

        $lelang = ProposalDocxBuilder::for($this->proyek(Project::PURPOSE_LELANG))->sectionsForEditor();
        $jual   = ProposalDocxBuilder::for($this->proyek(Project::PURPOSE_JUAL_BELI))->sectionsForEditor();

        $ambil = fn (array $bab) => collect($bab)->firstWhere('key', 'kerahasiaan');

        $this->assertSame('Klausul kerahasiaan khusus Lelang.', $ambil($lelang)['baku']);
        $this->assertTrue($ambil($lelang)['dari_default']);

        $this->assertNotSame('Klausul kerahasiaan khusus Lelang.', $ambil($jual)['baku']);
        $this->assertFalse($ambil($jual)['dari_default']);
    }

    public function test_teks_semua_tujuan_ditimpa_teks_khusus_tujuan(): void
    {
        ProposalSectionDefault::create([
            'proposal_purpose' => ProposalSectionDefault::SEMUA_TUJUAN,
            'section_key'      => 'pendamping',
            'body'             => 'Pendamping versi umum.',
        ]);
        ProposalSectionDefault::create([
            'proposal_purpose' => Project::PURPOSE_LELANG,
            'section_key'      => 'pendamping',
            'body'             => 'Pendamping versi lelang.',
        ]);

        $ambil = fn (Project $p) => collect(ProposalDocxBuilder::for($p)->sectionsForEditor())
            ->firstWhere('key', 'pendamping')['baku'];

        $this->assertSame('Pendamping versi lelang.', $ambil($this->proyek(Project::PURPOSE_LELANG)));
        $this->assertSame('Pendamping versi umum.', $ambil($this->proyek(Project::PURPOSE_JUAL_BELI)));
    }

    public function test_placeholder_diganti_nama_klien(): void
    {
        ProposalSectionDefault::create([
            'proposal_purpose' => ProposalSectionDefault::SEMUA_TUJUAN,
            'section_key'      => 'pendamping',
            'body'             => 'Pendamping lapangan disediakan oleh :klien.',
        ]);

        $baku = collect(ProposalDocxBuilder::for($this->proyek(Project::PURPOSE_JUAL_BELI))->sectionsForEditor())
            ->firstWhere('key', 'pendamping')['baku'];

        $this->assertSame('Pendamping lapangan disediakan oleh PT Klien Baku.', $baku);
    }

    public function test_override_proyek_menang_atas_teks_baku(): void
    {
        ProposalSectionDefault::create([
            'proposal_purpose' => ProposalSectionDefault::SEMUA_TUJUAN,
            'section_key'      => 'pendamping',
            'body'             => 'Pendamping versi baku.',
        ]);

        $project = $this->proyek(Project::PURPOSE_JUAL_BELI);
        $project->sectionTexts()->create(['section_key' => 'pendamping', 'body' => 'Pendamping khusus proyek ini.']);

        $bab = collect(ProposalDocxBuilder::for($project->fresh())->sectionsForEditor())->firstWhere('key', 'pendamping');

        $this->assertTrue($bab['overridden']);
        $this->assertSame('Pendamping khusus proyek ini.', $bab['text']);
        $this->assertSame('Pendamping versi baku.', $bab['baku']);
    }

    public function test_reset_mengembalikan_teks_bawaan_sistem(): void
    {
        ProposalSectionDefault::create([
            'proposal_purpose' => Project::PURPOSE_LELANG,
            'section_key'      => 'kerahasiaan',
            'body'             => 'Klausul sementara.',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('settings.proposalDefaults.reset', 'kerahasiaan'), ['tujuan' => Project::PURPOSE_LELANG])
            ->assertRedirect();

        $this->assertDatabaseMissing('proposal_section_defaults', ['section_key' => 'kerahasiaan']);
    }

    public function test_bab_yang_tidak_berlaku_untuk_tujuan_itu_ditolak(): void
    {
        // "waktu_ekspos" hanya ada di proposal Lelang.
        $this->actingAs($this->admin)
            ->put(route('settings.proposalDefaults.update', 'waktu_ekspos'), [
                'tujuan' => Project::PURPOSE_JUAL_BELI,
                'body'   => 'Tidak boleh.',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('proposal_section_defaults', ['section_key' => 'waktu_ekspos']);
    }

    public function test_proposal_word_memakai_teks_baku(): void
    {
        ProposalSectionDefault::create([
            'proposal_purpose' => Project::PURPOSE_JUAL_BELI,
            'section_key'      => 'pendamping',
            'body'             => 'Kalimat pendamping hasil pengaturan sistem.',
        ]);

        $path = ProposalDocxBuilder::for($this->proyek(Project::PURPOSE_JUAL_BELI))->save();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('Kalimat pendamping hasil pengaturan sistem.', $xml);
    }
}
