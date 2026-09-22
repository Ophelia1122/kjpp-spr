<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsAppNotification;
use App\Services\WhatsAppNotifier;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pengaturan notifikasi WhatsApp per tombol alur (2026-09-22). */
class NotifikasiWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Project $proyek;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Administrator Uji', 'email' => 'admin.uji@example.test', 'password' => 'rahasia123',
            'role_id' => Role::where('slug', Role::ADMINISTRATOR)->value('id'), 'is_active' => true,
            'jabatan' => User::JABATAN_PENANGGUNG_JAWAB, 'whatsapp_number' => '0812 1111 2222',
        ]);
        $klien = Client::create(['client_name' => 'PT Klien Uji', 'client_type' => 'Korporat', 'address' => 'Jl. Uji']);
        $this->proyek = Project::create([
            'proposal_number' => '00777/UJI/IX/2026', 'proposal_date' => now()->toDateString(),
            'instructing_client_id' => $klien->id, 'signed_by_user_id' => $this->admin->id,
            'service_fee' => 1_000_000, 'fee_ppn_included' => true, 'report_style' => Project::REPORT_LONG,
            'sla_draft_days' => 5, 'sla_final_days' => 7, 'proposal_purpose' => Project::PURPOSE_PENJAMINAN_UTANG,
            'payment_scheme' => Project::PAYMENT_SCHEME_DP, 'status' => Project::STATUS_IN_PROGRESS,
            'assigned_appraiser_id' => $this->admin->id, 'assigned_appraiser' => $this->admin->name,
            'survey_date' => now()->toDateString(), 'asset_type' => 'Tanah', 'asset_address' => 'Jl. Objek Uji',
        ]);
    }

    public function test_bawaan_sama_dengan_pemicu_lama(): void
    {
        $this->assertTrue(WhatsAppNotification::forStep('submit_value')->enabled);
        $this->assertTrue(WhatsAppNotification::forStep('return_value')->enabled);
        $this->assertFalse(WhatsAppNotification::forStep('release_resume')->enabled);
        $this->assertSame(['reviewers'], WhatsAppNotification::forStep('confirm_draft')->recipients);
    }

    public function test_simpan_pengaturan_dan_render_pesan(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.whatsapp.notifications'), ['n' => [
                'release_resume' => [
                    'enabled'    => 1,
                    'recipients' => ['appraisers'],
                    'jabatan'    => [User::JABATAN_PENANGGUNG_JAWAB],
                    'group_jid'  => '120363000000000001@g.us',
                    'template'   => "Resume {nomor_proposal} oleh {oleh}\nCatatan: {catatan}\n{mention}",
                ],
            ]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $cfg = WhatsAppNotification::forStep('release_resume');
        $this->assertTrue($cfg->enabled);
        $this->assertSame(['appraisers', 'jabatan:' . User::JABATAN_PENANGGUNG_JAWAB], $cfg->recipients);
        $this->assertSame('120363000000000001@g.us', $cfg->group_jid);

        $users = WhatsAppNotifier::recipients($this->proyek, $cfg->recipients);
        $this->assertCount(1, $users, 'Penerima yang sama tidak boleh dobel.');

        // Catatan kosong: baris {catatan} hilang; mention memakai nomor ternormalisasi.
        $text = WhatsAppNotifier::render($cfg->template, $this->proyek, 'release_resume', $this->admin, null, $users);
        $this->assertSame("Resume 00777/UJI/IX/2026 oleh Administrator Uji\n@6281211112222", $text);
    }

    public function test_grup_harus_berakhiran_g_us(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.whatsapp.notifications'), ['n' => [
                'submit_value' => ['template' => 'x', 'group_jid' => 'bukan-grup'],
            ]])
            ->assertSessionHasErrors('n.submit_value.group_jid');
    }
}
