<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectValuationObject;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Membuat tiga proyek contoh Non-Penilaian — Kajian Kewajaran RAB, Studi
 * Kelayakan, dan Pengawasan Proyek — lengkap dengan objek, petugas, dan dua
 * invoice per proyek (satu lunas untuk mencoba Kwitansi, satu belum lunas).
 *
 * Dipakai untuk memeriksa hasil Proposal, Invoice, dan Kwitansi ketiganya
 * tanpa harus mengisi formulir satu per satu.
 *
 * Perintah ini HANYA MENAMBAH data. Jalankan di komputer lokal saja.
 */
class BuatContohNonPenilaian extends Command
{
    protected $signature = 'dummy:non-penilaian {--hapus : Hapus proyek contoh yang dibuat perintah ini}';

    protected $description = 'Buat tiga proyek contoh Non-Penilaian beserta invoice & kwitansinya';

    /** Penanda nomor proposal supaya mudah dikenali dan dihapus lagi. */
    private const AWALAN = 'CONTOH-NP';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Perintah ini tidak boleh dijalankan di lingkungan production.');

            return self::FAILURE;
        }

        if ($this->option('hapus')) {
            return $this->hapus();
        }

        $penandaTangan = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->first();

        if (! $penandaTangan) {
            $this->error('Tidak ada pengguna ber-role Administrator. Jalankan seeder dasar dulu.');

            return self::FAILURE;
        }

        foreach ($this->contoh() as $i => $data) {
            $proyek = $this->buat($data, $penandaTangan, $i + 1);

            $this->newLine();
            $this->info($data['jenis']);
            $this->line('  Proposal  : ' . $proyek->proposal_number);
            $this->line('  Proyek    : ' . route('proposals.show', $proyek));
            foreach ($proyek->invoices as $inv) {
                $this->line('  ' . str_pad($inv->status === Invoice::STATUS_PAID ? 'Lunas' : 'Belum', 9)
                    . ': ' . $inv->invoice_number
                    . ($inv->status === Invoice::STATUS_PAID ? '  (Kwitansi tersedia)' : ''));
            }
        }

        $this->newLine();
        $this->line('Unduh Proposal, Surat Tugas, Invoice, dan Kwitansi dari halaman proyek masing-masing.');
        $this->line('Hapus lagi dengan: php artisan dummy:non-penilaian --hapus');

        return self::SUCCESS;
    }

    /** Tiga jenis pekerjaan beserta contoh isiannya. */
    private function contoh(): array
    {
        return [
            [
                'jenis'   => Project::CONSULTING_RAB,
                'klien'   => 'PT Contoh Kajian Sejahtera',
                'alamat'  => 'Gedung Danamon Matraman 6th floor, Jl. Raya Matraman No. 52, Jakarta Timur 13150',
                'proyek'  => 'Ruko/Rukan pada Project Pantai Indah Mutiara',
                'lokasi'  => 'Kawasan Golden Prawn, Tj. Buntung, Kec. Bengkong, Kota Batam, Kepulauan Riau',
                'uraian'  => 'Rencana pengembangan Ruko/Rukan pada Project Pantai Indah Mutiara yang dikembangkan oleh PT Contoh Kajian Sejahtera yang terletak di Kawasan Golden Prawn, Tj. Buntung, Kec. Bengkong, Kota Batam.',
                'attn'    => 'Bapak Sinyo, Kepala Divisi Kredit',
                'fee'     => 15_000_000,
            ],
            [
                'jenis'   => Project::CONSULTING_FS,
                'klien'   => 'PT Contoh Kelayakan Nusantara',
                'alamat'  => 'Jl. Raya Mantup Lamongan KM 16 RT 05 RW 01, Desa Dumpiagung, Kecamatan Kembangbahu, Kabupaten Lamongan, Jawa Timur',
                'proyek'  => 'Pabrik Pengolahan Tembakau',
                'lokasi'  => 'Jl. Raya Mantup Lamongan KM 16 RT 05 RW 01, Desa Dumpiagung, Kec. Kembangbahu, Kab. Lamongan, Jawa Timur',
                'uraian'  => 'Rencana pembangunan Pabrik Pengolahan Tembakau yang dikembangkan oleh PT Contoh Kelayakan Nusantara yang terletak di Desa Dumpiagung, Kecamatan Kembangbahu, Kabupaten Lamongan.',
                'attn'    => 'Bapak Direktur Utama',
                'fee'     => 100_000_000,
            ],
            [
                'jenis'   => Project::CONSULTING_PENGAWASAN,
                'klien'   => 'PT Contoh Pengawasan Bali',
                'alamat'  => 'Pantai Labuan Sait BJ Dinas Labuan Sait, Pecatu, Kuta Selatan, Kab. Badung, Bali 80363',
                'proyek'  => 'Resort Tahap II',
                'lokasi'  => 'Sunset Road, Lembongan Island, Nusa Penida, Klungkung Regency, Bali',
                'uraian'  => 'Rencana pembangunan Resort Tahap II yang dikembangkan oleh PT Contoh Pengawasan Bali yang terletak di Sunset Road, Lembongan Island, Nusa Penida, Klungkung, Bali.',
                'attn'    => 'Bapak Manajer Proyek',
                'fee'     => 15_000_000,
            ],
        ];
    }

    private function buat(array $data, User $penandaTangan, int $urut): Project
    {
        return DB::transaction(function () use ($data, $penandaTangan, $urut) {
            $klien = Client::firstOrCreate(
                ['client_name' => $data['klien']],
                ['client_type' => 'Korporat', 'address' => $data['alamat']],
            );

            $waktu = now();

            $proyek = Project::create([
                'proposal_number'  => sprintf('%s-%d/%s', self::AWALAN, $urut, $waktu->format('m/Y')),
                'proposal_date'    => $waktu->copy()->subDays(7)->toDateString(),

                'service_type'     => Project::SERVICE_KONSULTASI,
                'consulting_type'  => $data['jenis'],
                'proposal_purpose' => $data['jenis'],
                'work_object_description' => $data['uraian'],
                'letter_attn'      => $data['attn'],

                'instructing_client_id' => $klien->id,
                'signed_by_user_id'     => $penandaTangan->id,

                'service_fee'      => $data['fee'],
                'initial_service_fee' => $data['fee'],
                'fee_ppn_included' => true,
                'payment_scheme'   => Project::PAYMENT_SCHEME_DP,
                'payment_terms'    => [50, 50],

                'report_style'     => Project::REPORT_LONG,
                'sla_draft_days'   => 7,
                'sla_final_days'   => 14,

                'status'           => Project::STATUS_IN_PROGRESS,
                'asset_type'       => 'Lainnya',
                'asset_address'    => $data['lokasi'],

                'assigned_appraiser' => $penandaTangan->name,
                'survey_date'        => $waktu->copy()->subDays(3)->toDateString(),
            ]);

            $proyek->intendedUsers()->sync([$klien->id]);

            ProjectValuationObject::create([
                'project_id' => $proyek->id,
                'sort_order' => 1,
                'location'   => $data['lokasi'],
                // Catatan Tambahan = Nama Singkat Proyek pada Non-Penilaian.
                'notes'      => $data['proyek'],
            ]);

            // Tim Pelaksana: Posisi & Kualifikasi diketik manual.
            $tim = [
                ['Ketua Tim', 'Penilai Properti'],
                ['Ahli Keuangan', 'Penilai Properti'],
                ['Supporting (Tim Analis)', '-'],
            ];
            foreach (User::where('is_active', true)->orderBy('id')->take(3)->get() as $i => $anggota) {
                $proyek->assignmentStaff()->create([
                    'user_id'       => $anggota->id,
                    'sort_order'    => $i + 1,
                    'position'      => $tim[$i][0],
                    'qualification' => $tim[$i][1],
                ]);
            }

            // Dua invoice: Tahap I sudah lunas (bisa dicetak Kwitansi),
            // Tahap II belum lunas.
            $separuh = round((float) $proyek->total_fee / 2, 2);

            Invoice::create([
                'project_id'       => $proyek->id,
                'invoice_number'   => sprintf('%s-%d-1/%s', self::AWALAN, $urut, $waktu->format('m/Y')),
                'kwitansi_number'  => sprintf('%s-%d-KWT/%s', self::AWALAN, $urut, $waktu->format('m/Y')),
                'invoice_date'     => $waktu->copy()->subDays(5)->toDateString(),
                'invoice_type'     => Invoice::TYPE_DP,
                'amount'           => $separuh,
                'percentage'       => 50,
                'status'           => Invoice::STATUS_PAID,
                'payment_date'     => $waktu->copy()->subDays(4)->toDateString(),
                'term_description' => 'Fee Tahap I (DP)',
            ]);

            Invoice::create([
                'project_id'       => $proyek->id,
                'invoice_number'   => sprintf('%s-%d-2/%s', self::AWALAN, $urut, $waktu->format('m/Y')),
                'invoice_date'     => $waktu->copy()->subDay()->toDateString(),
                'invoice_type'     => Invoice::TYPE_PELUNASAN,
                'amount'           => (float) $proyek->total_fee - $separuh,
                'percentage'       => 50,
                'status'           => Invoice::STATUS_UNPAID,
                'term_description' => 'Fee Tahap II (Pelunasan)',
            ]);

            return $proyek->fresh('invoices');
        });
    }

    private function hapus(): int
    {
        $proyek = Project::withTrashed()->where('proposal_number', 'like', self::AWALAN . '%')->get();

        foreach ($proyek as $p) {
            $p->invoices()->delete();
            $p->valuationObjects()->delete();
            $p->assignmentStaff()->delete();
            $p->intendedUsers()->detach();
            $p->forceDelete();
        }

        Client::withTrashed()->where('client_name', 'like', 'PT Contoh %')->each(function (Client $c) {
            if ($c->projects()->withTrashed()->doesntExist()) {
                $c->forceDelete();
            }
        });

        $this->info($proyek->count() . ' proyek contoh dihapus.');

        return self::SUCCESS;
    }
}
