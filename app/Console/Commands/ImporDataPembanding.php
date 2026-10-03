<?php

namespace App\Console\Commands;

use App\Models\LandValuePoint;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor berkas "Data Pembanding" (satu .xlsx per tahun) — data penawaran dan
 * transaksi pasar hasil survei (2026-09-27, permintaan user). Berbeda dari
 * `import:data-aset` yang berisi kesimpulan penilaian KJPP sendiri; keduanya
 * disimpan di tabel yang sama tapi dibedakan lewat kolom data_type.
 *
 *   php artisan import:data-pembanding
 *   php artisan import:data-pembanding --path="D:\data" --bersihkan
 */
class ImporDataPembanding extends Command
{
    protected $signature = 'import:data-pembanding
        {--path= : Folder berisi berkas "Data Pembanding *.xlsx" (bawaan: storage/app/private/data-pembanding)}
        {--bersihkan : Hapus seluruh data pembanding lama dulu}
        {--uji : Tampilkan hasil bacaan tanpa menyimpan}';

    protected $description = 'Impor data pembanding pasar (penawaran/transaksi) dari berkas xlsx';

    private const KOLOM = [
        'no'        => 'NO',
        'nomor'     => 'Nomor Laporan',
        'tanggal'   => 'Tanggal Penilaian',
        'jenis'     => 'Jenis Properti',
        'luas_t'    => 'Luas Tanah',
        'luas_b'    => 'Luas Bangunan',
        'alamat'    => 'Alamat Data Pembanding',
        'provinsi'  => 'Provinsi Data Pembanding',
        'kota'      => 'Kabupaten - Kota Data Pembanding',
        'rate'      => 'Nilai Permeter Tanah',
        'rate_bgn'  => 'Nilai Permeter Bangunan',
        'total'     => 'Nilai Penawaran Total',
        'tahun'     => 'Tahun Data',
        'transaksi' => 'Transaksi/Penawaran',
        'sumber'    => 'Nama Sumber Data',
        'telepon'   => 'Nomor Telepon',
        'status'    => 'Status Sumber Data',
        'lat'       => 'Latitude',
        'lon'       => 'Longitude',
    ];

    /** Nama kecamatan & kelurahan berbeda antar tahun, jadi dicari longgar. */
    private const KOLOM_LONGGAR = [
        'kecamatan' => 'Kecamatan',
        'kelurahan' => 'Kelurahan',
        // Rincian penawaran; opsional, jadi berkas tanpa kolom ini tetap terbaca.
        'tot_tnh'   => 'Nilai Penawaran Tanah',
        'tot_bgn'   => 'Nilai Penawaran Bangunan',
    ];

    private const RATE_MIN = 50_000;
    private const RATE_MAX = 200_000_000;

    public function handle(): int
    {
        // Satu berkas berisi belasan ribu baris; PhpSpreadsheet memuatnya
        // sekaligus, jadi batas memori bawaan tidak cukup.
        ini_set('memory_limit', '2G');

        $path = $this->option('path') ?: storage_path('app/private/data-pembanding');

        if (! is_dir($path)) {
            $this->error("Folder tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $berkas = glob(rtrim($path, '\\/') . '/*.xlsx') ?: [];

        // Berkas "2019" dan "2019.R1" isinya sama persis; yang R1 dilewati
        // supaya datanya tidak dobel.
        $berkas = array_values(array_filter($berkas, function ($f) {
            if (preg_match('/\.R\d+\.xlsx$/i', $f)) {
                $this->warn('Dilewati (revisi ganda): ' . basename($f));

                return false;
            }

            return true;
        }));

        if (! $berkas) {
            $this->error("Tidak ada berkas .xlsx di: {$path}");

            return self::FAILURE;
        }

        if ($this->option('bersihkan') && ! $this->option('uji')) {
            $jml = LandValuePoint::where('data_type', LandValuePoint::TIPE_PEMBANDING)->delete();
            $this->warn("{$jml} data pembanding lama dihapus.");
        }

        $ringkas = ['baris' => 0, 'simpan' => 0, 'koordinat' => 0, 'harga' => 0, 'ekstrem' => 0, 'kembar' => 0];
        $bar = $this->output->createProgressBar(count($berkas));
        $bar->start();

        foreach ($berkas as $f) {
            foreach ($this->impor($f) as $k => $v) {
                $ringkas[$k] += $v;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->line('Berkas dibaca       : ' . count($berkas));
        $this->line('Baris pembanding    : ' . $ringkas['baris']);
        $this->line('Dilewati (koordinat): ' . $ringkas['koordinat']);
        $this->line('Dilewati (tanpa Rp) : ' . $ringkas['harga']);
        $this->line('Dilewati (ekstrem)  : ' . $ringkas['ekstrem']);
        $this->line('Dilewati (kembar)   : ' . $ringkas['kembar']);
        $this->info('Titik tersimpan     : ' . $ringkas['simpan']);

        if ($this->option('uji')) {
            $this->warn('Mode --uji: tidak ada yang disimpan.');

            return self::SUCCESS;
        }

        $this->line('Tujuan penilaian    : ' . $this->isiTujuan() . ' baris terisi');

        return self::SUCCESS;
    }

    /**
     * Berkas pembanding tidak memuat tujuan penilaian, tetapi nomor laporannya
     * sama dengan data aset. Tujuannya disalin lewat nomor laporan itu
     * (2026-09-27): tanpa langkah ini saringan tujuan tidak menemukan apa pun
     * pada data pembanding.
     */
    private function isiTujuan(): int
    {
        // Satu pernyataan UPDATE, bukan satu kueri per nomor laporan: ada
        // ribuan nomor dan cara per-baris makan belasan menit.
        return DB::update(
            'UPDATE land_value_points p
                JOIN (
                    SELECT report_number, MIN(purpose) AS tujuan
                      FROM land_value_points
                     WHERE data_type = ? AND purpose IS NOT NULL AND report_number IS NOT NULL
                  GROUP BY report_number
                ) a ON a.report_number = p.report_number
                SET p.purpose = a.tujuan
              WHERE p.data_type = ? AND p.purpose IS NULL',
            [LandValuePoint::TIPE_ASET, LandValuePoint::TIPE_PEMBANDING],
        );
    }

    /** @return array{baris:int,simpan:int,koordinat:int,harga:int,ekstrem:int} */
    /** Kunci pembanding yang sudah dibaca pada eksekusi ini (lihat imporSheet). */
    private array $terlihat = [];

    private function impor(string $file): array
    {
        $n = ['baris' => 0, 'simpan' => 0, 'koordinat' => 0, 'harga' => 0, 'ekstrem' => 0, 'kembar' => 0];
        $label = basename($file);

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $buku = $reader->load($file);

        // Hapus baris lama berkas ini SEKALI di depan, lalu baca SEMUA sheet.
        // Berkas 2025 berisi 12 sheet bulanan; dulu hanya sheet pertama yang
        // dibaca, jadi 11 bulan hilang tanpa peringatan (2026-10-03).
        if (! $this->option('uji')) {
            LandValuePoint::where('source_file', $label)->delete();
        }

        foreach ($buku->getAllSheets() as $sheet) {
            $this->imporSheet($sheet, $label, $n);
        }

        return $n;
    }

    /** @param array{baris:int,simpan:int,koordinat:int,harga:int,ekstrem:int,kembar:int} $n */
    private function imporSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $label, array &$n): void
    {
        $baris = $sheet->toArray(null, true, false, false);
        $namaSheet = $label . ' / ' . $sheet->getTitle();

        $iHeader = null;
        foreach (array_slice($baris, 0, 10) as $i => $r) {
            if ($r && collect($r)->contains(fn ($c) => trim((string) $c) === 'NO')) {
                $iHeader = $i;
                break;
            }
        }

        if ($iHeader === null) {
            $this->newLine();
            $this->warn("Header tidak ditemukan, dilewati: {$namaSheet}");

            return;
        }

        $peta = [];
        foreach ($baris[$iHeader] as $kolom => $judul) {
            $judul = trim((string) $judul);

            foreach (self::KOLOM as $kunci => $nama) {
                if (strcasecmp($judul, $nama) === 0) {
                    $peta[$kunci] = $kolom;
                }
            }

            foreach (self::KOLOM_LONGGAR as $kunci => $awalan) {
                if (! isset($peta[$kunci]) && stripos($judul, $awalan) === 0) {
                    $peta[$kunci] = $kolom;
                }
            }
        }

        $hilang = array_diff(array_keys(self::KOLOM), array_keys($peta));

        if ($hilang) {
            $this->newLine();
            $this->warn("Kolom hilang (" . implode(', ', $hilang) . "), dilewati: {$namaSheet}");

            return;
        }

        $simpan = [];

        foreach (array_slice($baris, $iHeader + 1) as $r) {
            $ambil = fn (string $k) => isset($peta[$k]) ? trim((string) ($r[$peta[$k]] ?? '')) : '';

            if (! ctype_digit($ambil('no'))) {
                continue;   // baris nama bulan / pemisah
            }

            $n['baris']++;

            $lat = $this->koordinat($ambil('lat'), true);
            $lon = $this->koordinat($ambil('lon'), false);

            if ($lat === null || $lon === null) {
                $n['koordinat']++;
                continue;
            }

            // Ruko, unit apartemen, office space & kios dihitung per m²
            // BANGUNAN (2026-09-27, permintaan user).
            $jenis  = $ambil('jenis');
            $kelas  = LandValuePoint::kelas($jenis);
            $satuan = LandValuePoint::SATUAN[$kelas];

            // Satuan mengikuti kelas dan tidak pernah berpindah: kalau
            // angkanya tidak ada pada satuan itu, barisnya dilewati. Dulu
            // sempat jatuh ke satuan lain, sehingga satu kelas berisi dua
            // satuan dan kartu hasil menuduh "campuran satuan"
            // (2026-09-27, feedback user).
            $rate = $this->rupiah($satuan === 'bangunan' ? $ambil('rate_bgn') : $ambil('rate'));

            if ($rate <= 0) {
                $n['harga']++;
                continue;
            }

            if ($rate < self::RATE_MIN || $rate > self::RATE_MAX) {
                $n['ekstrem']++;
                continue;
            }

            $tanggal = $this->tanggal($ambil('tanggal'));
            $tahun   = (int) $ambil('tahun') ?: (int) ($tanggal?->year ?: 0);

            // Tahun di masa depan = salah ketik (berkas 2025 memuat 2027, 2029,
            // 2030 pada baris bertanggal penilaian 2025): pakai tahun tanggal
            // penilaian. Tahun ini sendiri boleh — laporan Januari memuat
            // penilaian akhir tahun lalu (2026-10-03).
            if ($tahun < 2000 || $tahun > (int) now()->year) {
                $tahun = (int) ($tanggal?->year ?: now()->year);
            }

            // Laporan yang menilai beberapa objek mencantumkan daftar pembanding
            // yang SAMA untuk tiap objek, jadi satu komparabel muncul berulang.
            // Dibiarkan, ia terhitung berkali-kali di median berbobot (±5% baris
            // berkas 2019-2025 kembar). Cukup satu per komparabel (2026-10-03).
            $kunci = md5(implode('|', [
                $ambil('nomor'), mb_strtolower($ambil('alamat')),
                (int) $this->rupiah($ambil('total')),
                (int) $this->rupiah($ambil('luas_t')), (int) $this->rupiah($ambil('luas_b')),
                (int) $rate,
            ]));

            if (isset($this->terlihat[$kunci])) {
                $n['kembar']++;
                continue;
            }

            $this->terlihat[$kunci] = true;

            $simpan[] = [
                'data_type'      => LandValuePoint::TIPE_PEMBANDING,
                'property_class' => $kelas,
                'rate_basis'     => $satuan,
                'offer_total'    => (int) $this->rupiah($ambil('total')) ?: null,
                'offer_land'     => (int) $this->rupiah($ambil('tot_tnh')) ?: null,
                'offer_building' => (int) $this->rupiah($ambil('tot_bgn')) ?: null,
                'building_rate'  => (int) $this->rupiah($ambil('rate_bgn')) ?: null,
                'offer_type'     => mb_substr($ambil('transaksi'), 0, 20) ?: null,
                'report_number'  => mb_substr($ambil('nomor'), 0, 100) ?: null,
                'valuation_date' => $tanggal?->toDateString(),
                'valuation_year' => $tahun,
                'latitude'       => $lat,
                'longitude'      => $lon,
                'property_type'  => mb_substr($jenis, 0, 80) ?: null,
                'property_group' => LandValuePoint::kelompok($jenis),
                'land_rate'      => (int) $rate,
                'land_area'      => (int) $this->rupiah($ambil('luas_t')) ?: null,
                'building_area'  => (int) $this->rupiah($ambil('luas_b')) ?: null,
                'province'       => mb_substr($ambil('provinsi'), 0, 60) ?: null,
                'city'           => mb_substr($ambil('kota'), 0, 80) ?: null,
                'district'       => mb_substr($ambil('kecamatan'), 0, 80) ?: null,
                'village'        => mb_substr($ambil('kelurahan'), 0, 80) ?: null,
                'address'        => mb_substr($ambil('alamat'), 0, 255) ?: null,
                'source_name'    => mb_substr($ambil('sumber'), 0, 120) ?: null,
                'source_phone'   => mb_substr($ambil('telepon'), 0, 40) ?: null,
                'source_status'  => mb_substr($ambil('status'), 0, 40) ?: null,
                'source_file'    => $label,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            $n['simpan']++;
        }

        if ($simpan && ! $this->option('uji')) {
            foreach (array_chunk($simpan, 500) as $potong) {
                LandValuePoint::insert($potong);
            }
        }
    }

    /**
     * Excel menyimpan koordinat dengan koma ribuan: "-6,339,724" berarti
     * -6.339724 dan "10,686,116" berarti 106.86116. Panjang digitnya tidak
     * seragam, jadi titik desimalnya dicari dengan membagi 10 sampai angkanya
     * masuk rentang wilayah Indonesia.
     */
    private function koordinat(string $v, bool $lintang): ?float
    {
        $negatif = str_starts_with(trim($v), '-');
        $digit   = preg_replace('/[^0-9]/', '', $v) ?? '';

        if ($digit === '' || (int) $digit === 0) {
            return null;
        }

        [$min, $maks] = $lintang ? [-11.5, 6.5] : [94.5, 141.5];
        $x = (float) $digit;

        for ($i = 0; $i < 12; $i++) {
            $nilai = $negatif ? -$x : $x;

            if ($nilai >= $min && $nilai <= $maks) {
                return $nilai;
            }

            $x /= 10;
        }

        return null;
    }

    private function rupiah(string $v): float
    {
        $angka = preg_replace('/[^0-9]/', '', $v) ?? '';

        return $angka === '' ? 0 : (float) $angka;
    }

    private function tanggal(string $v): ?Carbon
    {
        $v = trim($v);

        if ($v === '') {
            return null;
        }

        $bulan = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
        ];

        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/u', $v, $m)) {
            $b = $bulan[mb_strtolower($m[2])] ?? null;

            if ($b) {
                return Carbon::createFromFormat('Y-m-d', sprintf('%s-%s-%02d', $m[3], $b, $m[1]))->startOfDay();
            }
        }

        try {
            return Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }
    }
}
