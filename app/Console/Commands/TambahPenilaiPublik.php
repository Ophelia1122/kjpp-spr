<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Menambahkan akun Penilai Publik kantor beserta biodata untuk bab
 * "Penjelasan Status Penilai" (2026-09-22, dari dokumen "Status Penilai
 * Publik Pusat").
 *
 * Aman dijalankan berulang: akun yang emailnya sudah ada tidak dibuat
 * ulang; biodata hanya diisi pada kolom yang masih kosong, jadi data yang
 * sudah diedit lewat Kelola Pengguna tidak tertimpa. Akun baru diberi
 * password acak — atur password lewat Kelola Pengguna sebelum dipakai login.
 */
class TambahPenilaiPublik extends Command
{
    protected $signature = 'kjpp:penilai-publik {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Tambah/lengkapi akun Penilai Publik (jabatan Penanggung Jawab) beserta biodata status penilai';

    private const SEKTOR_5 = 'semua';
    private const SEKTOR_4 = 'tanpa_pasar_modal';

    /** Data dari dokumen resmi kantor. */
    private function penilai(): array
    {
        return [
            [
                'email' => 'budi@spr.com', 'name' => 'Ir. Budi Parasodjo, M.Ec.Dev., MAPPI (Cert.)',
                'izin_menkeu_no' => 'PB-1.13.00375', 'sk_menkeu_no' => '511/KM.1/2013', 'sk_menkeu_date' => '2013-07-23',
                'sttd_ojk_no' => 'KEP-528/KS.13/2026', 'sttd_ojk_date' => '2026-06-03',
                'pertanahan_izin_no' => '1415/SK-PT.01.01/VIII/2023', 'pertanahan_izin_date' => '2023-08-16',
                'klasifikasi' => 'Klasifikasi Bidang Jasa Properti dan Bisnis', 'sektor' => self::SEKTOR_5,
            ],
            [
                'email' => 'heru@spr.com', 'name' => 'Ir. Heru Setyabudi, ASEAN Eng., M.Ec.Dev., MAPPI (Cert.)',
                'izin_menkeu_no' => 'P-1.15.00425', 'sk_menkeu_no' => '328/KM.1/2015', 'sk_menkeu_date' => '2015-04-21',
                'sttd_ojk_no' => 'KEP-893/KS.13/2026', 'sttd_ojk_date' => '2026-06-18',
                'pertanahan_izin_no' => '6291/SK-PT.01/.01/XI/2025', 'pertanahan_izin_date' => '2025-11-03',
                'klasifikasi' => 'Klasifikasi Bidang Jasa Properti', 'sektor' => self::SEKTOR_5,
            ],
            [
                'email' => 'pipik@spr.com', 'name' => 'Pipik Firmansyah, S.Pd., M.Ec.Dev., MAPPI (Cert.)',
                'izin_menkeu_no' => 'P-1.20.00576', 'sk_menkeu_no' => '288/KM.1/2020', 'sk_menkeu_date' => '2020-05-19',
                'sttd_ojk_no' => 'KEP-832/KS.13/2026', 'sttd_ojk_date' => '2026-06-12',
                'pertanahan_izin_no' => '1877/SK-PT.01/.01/III/2025', 'pertanahan_izin_date' => '2025-03-20',
                'klasifikasi' => 'Klasifikasi Bidang Jasa Properti', 'sektor' => self::SEKTOR_5,
            ],
            [
                'email' => 'hery@spr.com', 'name' => 'Hery Purnomo S.T., MAPPI (Cert.)',
                'izin_menkeu_no' => 'P-1.24.00657', 'sk_menkeu_no' => '39/KM.1/2024', 'sk_menkeu_date' => '2024-01-24',
                'sttd_ojk_no' => 'KEP-982/KS.13/2026', 'sttd_ojk_date' => '2026-06-22',
                // Tanggal sesuai dokumen sumber (nomor surat berakhiran /2023).
                'pertanahan_izin_no' => '1612/SK-PT.01.01/X/2023', 'pertanahan_izin_date' => '2025-10-05',
                'klasifikasi' => 'Klasifikasi Bidang Jasa Properti', 'sektor' => self::SEKTOR_5,
            ],
            // Sudah ada di aplikasi; hanya melengkapi sektor OJK (tanpa izin pertanahan).
            [
                'email' => null, 'name' => 'Arief Rachman Setiady, S.M., M.M., MAPPI (Cert.)',
                'sektor' => self::SEKTOR_4,
            ],
        ];
    }

    public function handle(): int
    {
        $dry    = (bool) $this->option('dry-run');
        $roleId = Role::where('slug', Role::ADMINISTRATOR)->value('id');

        foreach ($this->penilai() as $row) {
            $sektor = $row['sektor'] === self::SEKTOR_5
                ? User::OJK_SECTORS
                : array_values(array_filter(User::OJK_SECTORS, fn ($x) => ! str_starts_with($x, 'Pasar Modal')));
            $bio = collect($row)->except(['email', 'name', 'sektor'])->all() + ['ojk_sectors' => $sektor];

            $user = ($row['email'] ? User::where('email', $row['email'])->first() : null)
                ?? User::where('name', $row['name'])->first();

            if (! $user) {
                if (! $row['email']) {
                    $this->warn("Lewati: {$row['name']} tidak ditemukan.");
                    continue;
                }
                $this->line("Buat akun baru: {$row['name']} <{$row['email']}>");
                if (! $dry) {
                    User::create($bio + [
                        'name' => $row['name'], 'email' => $row['email'],
                        'password' => Str::random(40), 'role_id' => $roleId, 'is_active' => true,
                        'jabatan' => User::JABATAN_PENANGGUNG_JAWAB,
                    ]);
                }
                continue;
            }

            // Hanya isi kolom yang masih kosong.
            $isi = array_filter($bio, fn ($v, $k) => blank($user->{$k}), ARRAY_FILTER_USE_BOTH);
            $this->line("Lengkapi {$user->name}: " . ($isi ? implode(', ', array_keys($isi)) : 'tidak ada yang kosong'));
            if (! $dry && $isi) {
                $user->fill($isi)->save();
            }
        }

        $this->info($dry ? 'Dry-run selesai, tidak ada yang disimpan.' : 'Selesai. Atur password akun baru lewat Kelola Pengguna.');

        return self::SUCCESS;
    }
}
