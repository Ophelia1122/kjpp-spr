<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unduh proposal beberapa proyek sekaligus sebagai .zip dari bilah "x dipilih"
 * di List Project (2026-09-26, permintaan user).
 */
class UnduhZipMassalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->firstOrFail();
    }

    private function proyek(string $nomor): Project
    {
        $klien = Client::firstOrCreate(
            ['client_name' => 'PT Uji Zip'],
            ['client_type' => 'Korporat', 'address' => 'Jl. Uji'],
        );

        $proyek = Project::create([
            'proposal_number'       => $nomor,
            'proposal_date'         => now()->toDateString(),
            'service_type'          => Project::SERVICE_PENILAIAN,
            'proposal_purpose'      => Project::PURPOSE_JUAL_BELI,
            'instructing_client_id' => $klien->id,
            'signed_by_user_id'     => $this->admin->id,
            'service_fee'           => 10_000_000,
            'report_style'          => Project::REPORT_LONG,
            'sla_draft_days'        => 7,
            'sla_final_days'        => 14,
            'payment_terms'         => [50, 50],
            'status'                => Project::STATUS_DRAFT,
            'asset_type'            => 'Tanah dan Bangunan',
            'asset_address'         => 'Jl. Objek',
        ]);

        $proyek->valuationObjects()->create([
            'sort_order'     => 1,
            'location'       => 'Jl. Objek',
            'asset_category' => 'Real Properti - Tanah dan Bangunan',
            'ownership_form' => 'SHM No. 1',
            'owner_name'     => 'PT Uji Zip',
        ]);

        return $proyek->fresh('valuationObjects');
    }

    public function test_zip_berisi_satu_dokumen_per_proyek(): void
    {
        $a = $this->proyek('ZIP/2026/001');
        $b = $this->proyek('ZIP/2026/002');

        $res = $this->actingAs($this->admin)
            ->post(route('proposals.exportZip'), ['ids' => [$a->id, $b->id], 'format' => 'word'])
            ->assertOk();

        $berkas = tempnam(sys_get_temp_dir(), 'zip') . '.zip';
        file_put_contents($berkas, $res->streamedContent());

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($berkas) === true);
        $this->assertSame(2, $zip->numFiles);

        $nama = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama[] = $zip->getNameIndex($i);
        }
        $zip->close();
        @unlink($berkas);

        foreach ($nama as $n) {
            $this->assertStringEndsWith('.docx', $n);
        }
    }

    public function test_format_dan_jumlah_dibatasi(): void
    {
        $a = $this->proyek('ZIP/2026/003');

        $this->actingAs($this->admin)
            ->post(route('proposals.exportZip'), ['ids' => [$a->id], 'format' => 'xls'])
            ->assertSessionHasErrors('format');

        $this->actingAs($this->admin)
            ->post(route('proposals.exportZip'), ['ids' => [], 'format' => 'pdf'])
            ->assertSessionHasErrors('ids');

        $this->actingAs($this->admin)
            ->post(route('proposals.exportZip'), ['ids' => range(1, 51), 'format' => 'pdf'])
            ->assertSessionHasErrors('ids');
    }
}
