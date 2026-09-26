<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur produksi Surveyor & Reviewer pada proyek Non-Penilaian (2026-09-26,
 * permintaan user): setelah daftar Petugas dibuka lebih awal dan jenis
 * layanan Non-Penilaian ditambahkan, pastikan status dan tombol aksi tiap
 * peran masih benar — tidak ada langkah yang hilang, bocor ke peran lain,
 * atau halaman yang gagal terbuka karena objeknya tanpa data penilaian.
 */
class AlurSurveyorKonsultasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksi;
    private User $keuangan;
    private User $surveyor;
    private User $reviewer;
    private Client $klien;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $peran = fn (string $slug) => User::whereHas('role', fn ($q) => $q->where('slug', $slug))->firstOrFail();

        $this->admin    = $peran(Role::ADMINISTRATOR);
        $this->produksi = $peran(Role::ADMIN_PRODUKSI);
        $this->keuangan = $peran(Role::ADMIN_KEUANGAN);
        $this->surveyor = $peran(Role::SURVEYOR);

        // Reviewer ditentukan dari JABATAN, bukan role (lihat User::isReviewer).
        $this->reviewer = User::create([
            'name' => 'Bagus (Reviewer)', 'email' => 'reviewer.uji@contoh.test',
            'password' => 'rahasia-uji-' . uniqid(), 'role_id' => $this->keuangan->role_id,
            'jabatan' => User::JABATAN_REVIEWER, 'is_active' => true,
        ]);

        $this->klien = Client::create([
            'client_name' => 'PT Uji Alur', 'client_type' => 'Korporat', 'address' => 'Jl. Uji',
        ]);
    }

    /** Proyek Non-Penilaian yang sudah berjalan & siap disurvei. */
    private function proyek(array $timpa = []): Project
    {
        $proyek = Project::create(array_merge([
            'proposal_number'  => 'ALUR/2026/001',
            'proposal_date'    => now()->toDateString(),
            'service_type'     => Project::SERVICE_KONSULTASI,
            'consulting_type'  => Project::CONSULTING_PENGAWASAN,
            'proposal_purpose' => Project::CONSULTING_PENGAWASAN,
            'work_object_description' => 'Pengawasan pembangunan Resort Tahap II.',
            'instructing_client_id'   => $this->klien->id,
            'signed_by_user_id'       => $this->admin->id,
            'service_fee'      => 50_000_000,
            'report_style'     => Project::REPORT_LONG,
            'sla_draft_days'   => 7,
            'sla_final_days'   => 14,
            'payment_terms'    => [50, 50],
            'status'           => Project::STATUS_IN_PROGRESS,
            // Ringkasan aset diisi controller dengan 'Lainnya' untuk
            // Non-Penilaian; di sini ditulis langsung karena tanpa form.
            'asset_type'       => 'Lainnya',
            'asset_address'    => 'Nusa Penida, Bali',
        ], $timpa));

        // Objek Non-Penilaian: lokasi + nama singkat proyek saja, tanpa
        // kategori aset / bentuk kepemilikan / atas nama.
        $proyek->valuationObjects()->create([
            'sort_order' => 1,
            'location'   => 'Nusa Penida, Bali',
            'notes'      => 'Resort Tahap II',
        ]);

        return $proyek->fresh('valuationObjects');
    }

    /** Isi penilai lapangan & tanggal survei — syarat semua tombol alur. */
    private function isiSurvei(Project $proyek): Project
    {
        $objek = $proyek->valuationObjects()->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('projects.inputSurveyData', $proyek), [
                'appraiser_ids' => [$this->surveyor->id],
                'surveys'       => [$objek->id => ['start' => now()->toDateString()]],
            ])
            ->assertRedirect()->assertSessionHasNoErrors();

        return $proyek->fresh();
    }

    public function test_surveyor_bisa_membuka_proyek_non_penilaian(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->surveyor)->get(route('proposals.show', $proyek))->assertOk();
        $this->actingAs($this->surveyor)->get(route('proposals.lengkap', $proyek))->assertOk();
        $this->actingAs($this->surveyor)->get(route('spj.index'))->assertOk();
        $this->actingAs($this->surveyor)->get(route('dashboard'))->assertOk();
    }

    /**
     * Objek Non-Penilaian tidak punya bentuk kepemilikan / atas nama, jadi
     * barisnya tidak boleh dicetak sebagai "Hak: – a.n." kosong.
     */
    public function test_kartu_objek_non_penilaian_tanpa_baris_hak_kosong(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->surveyor)->get(route('proposals.show', $proyek))
            ->assertOk()
            ->assertSee('Objek Pekerjaan (1)')
            ->assertDontSee('Hak: – a.n.', false);
    }

    /** Tahap proyek Non-Penilaian memakai istilah Summary, bukan Nilai/Resume. */
    public function test_label_tahap_memakai_istilah_non_penilaian(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->surveyor)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertRedirect();

        $proyek = $proyek->fresh();
        $this->assertSame('Review summary', $proyek->stage['label']);

        $proyek->update(['review_status' => Project::REVIEW_RELEASED]);
        $this->assertStringContainsString('Summary', $proyek->fresh()->stage['label']);
    }

    /** Surveyor hanya boleh menekan langkah miliknya sendiri. */
    public function test_langkah_alur_dibatasi_per_peran(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        // Surveyor: hanya "Submit Review Summary".
        $langkah = array_keys($proyek->availableWorkflowSteps($this->surveyor));
        $this->assertSame(['submit_value'], $langkah);

        // Reviewer (tanpa izin survei) belum punya tombol apa pun di tahap ini.
        $this->assertSame([], array_keys($proyek->availableWorkflowSteps($this->reviewer)));

        // Admin Produksi menjadwalkan survei, tetapi TIDAK boleh mengajukan
        // review — itu tugas penilai lapangan (2026-09-26, permintaan user).
        $this->assertSame([], array_keys($proyek->availableWorkflowSteps($this->produksi)));

        $this->actingAs($this->produksi)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertForbidden();

        // Penilai lapangan proyek lain juga tidak boleh menyeberang.
        $penilaiLain = User::create([
            'name' => 'Surveyor Lain', 'email' => 'surveyor.lain@contoh.test',
            'password' => 'rahasia-uji-' . uniqid(), 'role_id' => $this->surveyor->role_id,
            'is_active' => true,
        ]);
        $this->actingAs($penilaiLain)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertForbidden();

        // Langkah milik peran lain ditolak walau URL-nya ditebak.
        $this->actingAs($this->surveyor)
            ->post(route('projects.workflow', [$proyek, 'release_resume']))
            ->assertForbidden();
    }

    /** Tombol alur belum muncul sebelum penilai & tanggal survei diisi. */
    public function test_tidak_ada_tombol_sebelum_survei_dijadwalkan(): void
    {
        $proyek = $this->proyek();

        $this->assertSame([], $proyek->availableWorkflowSteps($this->surveyor));

        $this->actingAs($this->surveyor)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertForbidden();
    }

    /** Seluruh alur produksi dari survei sampai proyek ditutup. */
    public function test_alur_penuh_non_penilaian_sampai_selesai(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $jalan = function (User $pengguna, string $step, ?string $catatan = null) use (&$proyek) {
            $this->actingAs($pengguna)
                ->post(route('projects.workflow', [$proyek, $step]), $catatan ? ['note' => $catatan] : [])
                ->assertRedirect()->assertSessionHasNoErrors();
            $proyek = $proyek->fresh();
        };

        $jalan($this->surveyor, 'submit_value');
        $this->assertSame(Project::REVIEW_SUBMITTED, $proyek->review_status);

        $jalan($this->reviewer, 'release_resume');
        $this->assertSame(Project::REVIEW_RELEASED, $proyek->review_status);

        $jalan($this->reviewer, 'approve_value');
        $this->assertSame(Project::REVIEW_APPROVED, $proyek->review_status);

        $jalan($this->surveyor, 'submit_draft');
        $this->assertSame(Project::STAGE_DRAFT_SUBMITTED, $proyek->review_status);

        $jalan($this->produksi, 'confirm_draft');
        $this->assertSame(Project::STAGE_DRAFT_CONFIRMED, $proyek->review_status);

        $jalan($this->reviewer, 'review_draft');
        $this->assertSame(Project::STAGE_DRAFT_REVIEWED, $proyek->review_status);

        $proyek->update(['final_report_number' => 'FIN/2026/001']);
        $jalan($this->produksi, 'mark_printed');
        $this->assertSame(Project::STAGE_PRINTED, $proyek->review_status);

        $jalan($this->keuangan, 'mark_signed');
        $this->assertSame(Project::STAGE_SIGNED, $proyek->review_status);

        // Tanpa Tanda Terima, penutupan proyek ditolak dengan pesan, bukan
        // diteruskan diam-diam.
        $this->actingAs($this->keuangan)
            ->post(route('projects.workflow', [$proyek, 'mark_delivered']))
            ->assertSessionHas('error');
        $this->assertSame(Project::STAGE_SIGNED, $proyek->fresh()->review_status);

        $proyek->deliveryReceipts()->create([
            'number'              => 'TT/2026/001',
            'delivery_date'       => now()->toDateString(),
            'recipient_client_id' => $this->klien->id,
            'documents'           => ['laporan' => 1],
        ]);

        $jalan($this->keuangan, 'mark_delivered');
        $this->assertSame(Project::STAGE_DELIVERED, $proyek->review_status);
    }

    /** Draft narasi juga hanya bisa ditandai penilai lapangan. */
    public function test_draft_narasi_hanya_penilai_lapangan(): void
    {
        $proyek = $this->isiSurvei($this->proyek());
        $proyek->update(['review_status' => Project::REVIEW_APPROVED]);

        $this->actingAs($this->produksi)
            ->post(route('projects.workflow', [$proyek, 'submit_draft']))
            ->assertForbidden();

        $this->actingAs($this->surveyor)
            ->post(route('projects.workflow', [$proyek, 'submit_draft']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(Project::STAGE_DRAFT_SUBMITTED, $proyek->fresh()->review_status);
    }

    /** Aturan yang sama berlaku pada proyek Penilaian biasa. */
    public function test_admin_produksi_juga_tidak_bisa_submit_di_proyek_penilaian(): void
    {
        $proyek = $this->isiSurvei($this->proyek([
            'proposal_number'  => 'NILAI/2026/009',
            'service_type'     => Project::SERVICE_PENILAIAN,
            'consulting_type'  => null,
            'proposal_purpose' => Project::PURPOSE_JUAL_BELI,
        ]));

        $this->actingAs($this->produksi)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertForbidden();

        $this->actingAs($this->surveyor)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(Project::REVIEW_SUBMITTED, $proyek->fresh()->review_status);
    }

    /** Administrator tetap bisa menggantikan bila penilainya berhalangan. */
    public function test_administrator_masih_bisa_menggantikan_penilai(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->admin)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(Project::REVIEW_SUBMITTED, $proyek->fresh()->review_status);
    }

    /** Pengembalian ke Surveyor tetap bekerja pada proyek Non-Penilaian. */
    public function test_pengembalian_ke_surveyor(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->surveyor)->post(route('projects.workflow', [$proyek, 'submit_value']));

        $this->actingAs($this->reviewer)
            ->post(route('projects.workflow', [$proyek, 'return_value']), ['note' => 'Angka progres perlu dicek ulang.'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNull($proyek->fresh()->review_status);

        // Catatan wajib: tanpa alasan, pengembalian ditolak.
        $this->actingAs($this->surveyor)->post(route('projects.workflow', [$proyek, 'submit_value']));
        $this->actingAs($this->reviewer)
            ->post(route('projects.workflow', [$proyek, 'return_value']))
            ->assertSessionHasErrors('note');
    }

    /** Surveyor tidak boleh menyentuh daftar Petugas maupun data proposal. */
    public function test_surveyor_tidak_bisa_mengubah_petugas_atau_proposal(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->surveyor)
            ->post(route('projects.assignmentStaff.store', $proyek), ['user_id' => $this->admin->id])
            ->assertForbidden();

        $this->actingAs($this->surveyor)->get(route('proposals.edit', $proyek))->assertForbidden();
    }

    /**
     * Peringatan "Tim Pelaksana belum diisi" hanya untuk yang berwenang
     * mengisinya — Surveyor tidak bisa berbuat apa-apa dengan pesan itu.
     */
    public function test_peringatan_tim_pelaksana_tidak_tampil_untuk_surveyor(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->surveyor)->get(route('proposals.show', $proyek))
            ->assertOk()
            ->assertDontSee('Tim Pelaksana belum diisi.');

        $this->actingAs($this->keuangan)->get(route('proposals.show', $proyek))
            ->assertOk()
            ->assertSee('Tim Pelaksana belum diisi.');
    }

    /** Dokumen yang boleh diunduh Surveyor tetap terbentuk untuk Non-Penilaian. */
    public function test_surveyor_bisa_mengunduh_surat_tugas_non_penilaian(): void
    {
        $proyek = $this->isiSurvei($this->proyek());
        $proyek->update([
            'assignment_letter_number' => 'ST/2026/001',
            'assignment_letter_date'   => now()->toDateString(),
        ]);
        $proyek->assignmentStaff()->create([
            'user_id' => $this->surveyor->id, 'sort_order' => 1, 'position' => 'Ketua Tim',
        ]);

        $this->actingAs($this->surveyor)
            ->get(route('projects.exportSuratTugasWord', $proyek->fresh()))
            ->assertOk();
    }

    /** Rekap SPJ ikut memuat proyek Non-Penilaian tanpa kategori aset. */
    public function test_spj_surveyor_memuat_proyek_non_penilaian(): void
    {
        $proyek = $this->isiSurvei($this->proyek());

        $this->actingAs($this->surveyor)
            ->get(route('spj.index', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]))
            ->assertOk()
            ->assertSee($proyek->proposal_number)
            ->assertSee('Resort Tahap II');
    }

    /** Proyek batal/selesai: tidak ada lagi tombol alur untuk siapa pun. */
    public function test_proyek_batal_tidak_punya_tombol_alur(): void
    {
        $proyek = $this->isiSurvei($this->proyek());
        $proyek->update(['status' => Project::STATUS_BATAL]);

        foreach ([$this->surveyor, $this->reviewer, $this->produksi, $this->admin] as $pengguna) {
            $this->assertSame([], $proyek->fresh()->availableWorkflowSteps($pengguna));
        }

        $this->actingAs($this->surveyor)
            ->post(route('projects.workflow', [$proyek, 'submit_value']))
            ->assertForbidden();
    }
}
