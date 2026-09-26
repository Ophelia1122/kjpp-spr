<?php

namespace App\Console\Commands;

use App\Models\LandValuePoint;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Impor berkas "Data Aset Pusat" (satu .xlsx per bulan, satu folder per
 * tahun) menjadi titik nilai tanah (2026-09-26, permintaan user).
 *
 * Bisa dijalankan ulang saat berkas tahun baru datang: baris lama dari
 * berkas yang sama dihapus dulu, jadi tidak dobel.
 *
 *   php artisan import:data-aset
 *   php artisan import:data-aset --path="D:\data 2025" --bersihkan
 */
class ImporDataAset extends Command
{
    protected $signature = 'import:data-aset
        {--path= : Folder berisi subfolder tahun (bawaan: storage/app/private/data-aset)}
        {--bersihkan : Kosongkan seluruh tabel dulu, bukan hanya berkas yang diimpor}
        {--uji : Tampilkan hasil bacaan tanpa menyimpan}';

    protected $description = 'Impor data aset pusat (xlsx) menjadi titik nilai tanah';

    /** Kolom yang dipakai; nama persis seperti header berkas pusat. */
    private const KOLOM = [
        'no'        => 'NO',
        'nomor'     => 'Nomor Laporan',
        'tanggal'   => 'Tanggal Penilaian',
        'alamat'    => 'Alamat Penilaian Aset',
        'provinsi'  => 'Provinsi Penilaian Aset',
        'kota'      => 'Kabupaten - Kota Penilaian Aset',
        'kecamatan' => 'Kecamatan Aset',
        'kelurahan' => 'Kelurahan - Desa Penilaian Aset',
        'jenis'     => 'Jenis Properti',
        'luas_t'    => 'Luas Tanah',
        'luas_b'    => 'Luas Bangunan',
        'rate'      => 'Nilai Permeter Tanah',
        'tahun'     => 'Tahun Data Penilaian',
        'lat'       => 'Latitude',
        'lon'       => 'Longitude',
    ];

    /**
     * Batas nilai yang masih masuk akal. Di luar ini hampir pasti salah ketik
     * (mis. Rp4.000/m² atau Rp473 juta/m²) dan akan merusak estimasi.
     */
    private const RATE_MIN = 50_000;
    private const RATE_MAX = 200_000_000;

    public function handle(): int
    {
        $path = $this->option('path') ?: storage_path('app/private/data-aset');

        if (! is_dir($path)) {
            $this->error("Folder tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $berkas = glob(rtrim($path, '\\/') . '/*/*.xlsx') ?: [];

        if (! $berkas) {
            $this->error("Tidak ada berkas .xlsx di dalam subfolder tahun pada: {$path}");

            return self::FAILURE;
        }

        if ($this->option('bersihkan') && ! $this->option('uji')) {
            LandValuePoint::query()->delete();
            $this->warn('Tabel titik nilai tanah dikosongkan.');
        }

        $ringkas = ['baris' => 0, 'simpan' => 0, 'koordinat' => 0, 'harga' => 0, 'ekstrem' => 0];
        $bar = $this->output->createProgressBar(count($berkas));
        $bar->start();

        foreach ($berkas as $f) {
            $hasil = $this->impor($f);
            foreach ($ringkas as $k => $v) {
                $ringkas[$k] = $v + $hasil[$k];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->line('Berkas dibaca       : ' . count($berkas));
        $this->line('Baris aset          : ' . $ringkas['baris']);
        $this->line('Dilewati (koordinat): ' . $ringkas['koordinat']);
        $this->line('Dilewati (tanpa Rp) : ' . $ringkas['harga']);
        $this->line('Dilewati (ekstrem)  : ' . $ringkas['ekstrem']);
        $this->info('Titik tersimpan     : ' . $ringkas['simpan']);

        if ($this->option('uji')) {
            $this->warn('Mode --uji: tidak ada yang disimpan.');
        }

        return self::SUCCESS;
    }

    /** @return array{baris:int,simpan:int,koordinat:int,harga:int,ekstrem:int} */
    private function impor(string $file): array
    {
        $n = ['baris' => 0, 'simpan' => 0, 'koordinat' => 0, 'harga' => 0, 'ekstrem' => 0];

        $label = basename(dirname($file)) . '/' . basename($file);

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($file)->getSheet(0);
        $baris = $sheet->toArray(null, true, false, false);

        // Header tidak selalu di baris 1 — berkas pusat diawali judul & baris
        // kosong. Dicari baris yang kolom pertamanya "NO".
        $iHeader = null;
        foreach (array_slice($baris, 0, 10) as $i => $r) {
            if (strtoupper(trim((string) ($r[0] ?? ''))) === 'NO') {
                $iHeader = $i;
                break;
            }
        }

        if ($iHeader === null) {
            $this->newLine();
            $this->warn("Header tidak ditemukan, dilewati: {$label}");

            return $n;
        }

        $peta = [];
        foreach ($baris[$iHeader] as $kolom => $judul) {
            $judul = trim((string) $judul);
            foreach (self::KOLOM as $kunci => $nama) {
                if (strcasecmp($judul, $nama) === 0) {
                    $peta[$kunci] = $kolom;
                }
            }
        }

        $wajib = array_diff(array_keys(self::KOLOM), array_keys($peta));
        if ($wajib) {
            $this->newLine();
            $this->warn("Kolom hilang (" . implode(', ', $wajib) . "), dilewati: {$label}");

            return $n;
        }

        if (! $this->option('uji')) {
            LandValuePoint::where('source_file', $label)->delete();
        }

        $simpan = [];

        foreach (array_slice($baris, $iHeader + 1) as $r) {
            $ambil = fn (string $k) => trim((string) ($r[$peta[$k]] ?? ''));

            if ($ambil('no') === '' || $ambil('nomor') === '') {
                continue;
            }

            $n['baris']++;

            $lat = $this->koordinat($ambil('lat'));
            $lon = $this->koordinat($ambil('lon'));

            // Batas kasar wilayah Indonesia — menangkap salah ketik seperti
            // lintang -10000 yang ada di data 2019-2024.
            if ($lat === null || $lon === null
                || $lat < -11.5 || $lat > 6.5 || $lon < 94.5 || $lon > 141.5) {
                $n['koordinat']++;
                continue;
            }

            $rate = $this->rupiah($ambil('rate'));

            if ($rate <= 0) {
                $n['harga']++;   // properti tanpa komponen tanah (mis. unit apartemen)
                continue;
            }

            if ($rate < self::RATE_MIN || $rate > self::RATE_MAX) {
                $n['ekstrem']++;
                continue;
            }

            $tanggal = $this->tanggal($ambil('tanggal'));
            $tahun   = (int) $ambil('tahun') ?: (int) ($tanggal?->year ?: 0);

            if ($tahun < 2000 || $tahun > (int) now()->year + 1) {
                $tahun = (int) ($tanggal?->year ?: now()->year);
            }

            $jenis = $ambil('jenis');

            $simpan[] = [
                'report_number'  => mb_substr($ambil('nomor'), 0, 100),
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

        return $n;
    }

    /** "-6,304484" / "-6.304484" -> float; kolom pusat kadang pakai koma. */
    private function koordinat(string $v): ?float
    {
        $v = str_replace(',', '.', trim($v));
        $v = preg_replace('/[^0-9.\-]/', '', $v) ?? '';

        // Titik ganda (mis. "-6.304.484") disatukan jadi satu desimal.
        if (substr_count($v, '.') > 1) {
            $bagian = explode('.', $v);
            $v = array_shift($bagian) . '.' . implode('', $bagian);
        }

        return is_numeric($v) ? (float) $v : null;
    }

    /** "Rp11.310.000" / "11310000" -> 11310000. */
    private function rupiah(string $v): float
    {
        $angka = preg_replace('/[^0-9]/', '', $v) ?? '';

        return $angka === '' ? 0 : (float) $angka;
    }

    /** "18 Januari 2024" -> Carbon. */
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
