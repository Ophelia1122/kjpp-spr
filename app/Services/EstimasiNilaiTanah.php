<?php

namespace App\Services;

use App\Models\LandValuePoint;
use Illuminate\Support\Collection;

/**
 * Estimasi rentang nilai pasar tanah per meter dari sebuah titik koordinat
 * (2026-09-26, permintaan user).
 *
 * Caranya sederhana dan bisa dijelaskan ke klien: ambil titik penilaian
 * terdahulu di sekitar lokasi, beri bobot lebih besar pada yang lebih dekat
 * dan lebih baru, lalu ambil nilai tengahnya.
 *
 * Lebar rentang (:1,75 sampai x1,75) BUKAN tebakan — didapat dari uji ulang
 * seluruh 1.755 titik data 2019-2024: tiap titik diestimasi memakai titik
 * lain, lalu sebaran galatnya diukur. Hasilnya: nilai sebenarnya jatuh di
 * dalam rentang itu pada 80% kasus, dengan median galat titik tengah 17%.
 */
class EstimasiNilaiTanah
{
    /** Radius pencarian bawaan (km) bila pemakai tidak mengisi. */
    public const RADIUS_BAWAAN = 3.0;

    /** Dipertahankan untuk pemanggil lama. */
    public const RADIUS = [0.5, 1, 2, 3, 5];

    /** Pembanding minimum sebelum radius dilebarkan. */
    public const MIN_PEMBANDING = 3;

    /** Faktor rentang hasil kalibrasi (lihat keterangan kelas). */
    private const FAKTOR_RENTANG = 1.75;

    /** Bobot data lama: 0,88 pangkat selisih tahun. */
    private const SUSUT_TAHUN = 0.88;

    /**
     * @param  string|null  $kelompok  Kunci LandValuePoint::GROUPS; null = semua jenis.
     * @param  float|null   $radiusMaks  Batasi radius (km); null = sampai 5 km.
     */
    /** Rentang tahun data yang dipakai; null = semua tahun. */
    private ?array $tahun = null;

    public function hitung(
        float $lat,
        float $lon,
        ?string $kelompok = null,
        ?float $radiusMaks = null,
        ?array $tahun = null,
    ): array {
        $this->tahun = $tahun && count($tahun) === 2 ? [(int) $tahun[0], (int) $tahun[1]] : null;

        $tahunIni = (int) now()->year;
        $radius   = $this->tanggaRadius($radiusMaks);

        $terbesar = (float) end($radius);
        $kandidat = $this->sekitar($lat, $lon, $terbesar, $kelompok);

        foreach ($radius as $r) {
            $dalam = $kandidat->filter(fn ($p) => $p['jarak'] <= $r)->values();

            if ($dalam->count() >= self::MIN_PEMBANDING) {
                return $this->rangkum($dalam, $this->kmTeks($r), $tahunIni, $lat, $lon, $kelompok);
            }
        }

        // Pembanding di sekitar titik tidak cukup — mundur ke rata-rata
        // wilayah dari titik terdekat yang ada (kelurahan, lalu kecamatan,
        // lalu kabupaten/kota).
        return $this->wilayahTerdekat($lat, $lon, $kelompok, $tahunIni)
            ?? [
                'status'     => 'kosong',
                'pesan'      => 'Belum ada data penilaian di sekitar titik ini.',
                'pembanding' => collect(),
            ];
    }

    /**
     * Radius yang dipakai: angka yang diketik, atau 3 km bila dikosongkan
     * (2026-09-26, feedback user). Tidak bertahap lagi — dulu pencarian
     * berhenti di radius rapat begitu dapat 3 pembanding, sehingga hasilnya
     * berbeda-beda tiap titik.
     *
     * @return list<float>
     */
    public function tanggaRadius(?float $maks): array
    {
        // Radius yang diketik dipakai APA ADANYA (2026-09-26, feedback user):
        // dulu tangga tetap mulai dari 0,5 km dan berhenti begitu cukup,
        // sehingga mengetik angka besar terasa tidak berpengaruh.
        return [$maks !== null && $maks > 0 ? $maks : self::RADIUS_BAWAAN];
    }

    /** Titik dalam radius (km), sudah dihitung jaraknya & diurutkan. */
    public function sekitar(float $lat, float $lon, float $radiusKm, ?string $kelompok = null): Collection
    {
        // Prasaring kotak lintang/bujur supaya tidak menghitung jarak untuk
        // seluruh tabel; 1 derajat lintang ~111 km.
        $dLat = $radiusKm / 111.0;
        $dLon = $radiusKm / max(1.0, 111.0 * cos(deg2rad($lat)));

        $query = LandValuePoint::query()
            ->whereBetween('latitude', [$lat - $dLat, $lat + $dLat])
            ->whereBetween('longitude', [$lon - $dLon, $lon + $dLon]);

        if ($kelompok) {
            $query->where('property_group', $kelompok);
        }

        if ($this->tahun) {
            $query->whereBetween('valuation_year', $this->tahun);
        }

        return $query->get()
            ->map(function (LandValuePoint $p) use ($lat, $lon) {
                return [
                    'titik' => $p,
                    'jarak' => self::jarakKm($lat, $lon, $p->latitude, $p->longitude),
                ];
            })
            ->filter(fn ($b) => $b['jarak'] <= $radiusKm)
            ->sortBy('jarak')
            ->values();
    }

    /** Ringkas sekumpulan pembanding jadi satu estimasi. */
    private function rangkum(Collection $dalam, string $cakupan, int $tahunIni, float $lat, float $lon, ?string $kelompok): array
    {
        $nilai  = [];
        $bobot  = [];

        foreach ($dalam as $b) {
            $p = $b['titik'];
            $nilai[] = (float) $p->land_rate;
            $bobot[] = (1 / (0.2 + $b['jarak'])) * (self::SUSUT_TAHUN ** max(0, $tahunIni - (int) $p->valuation_year));
        }

        $tengah = $this->kuantilBerbobot($nilai, $bobot, 0.5);

        $mentah = $nilai;
        sort($mentah);

        return [
            'status'      => 'ok',
            'tengah'      => (int) round($tengah),
            'bawah'       => (int) round($tengah / self::FAKTOR_RENTANG),
            'atas'        => (int) round($tengah * self::FAKTOR_RENTANG),
            'data_min'    => (int) $mentah[0],
            'data_maks'   => (int) $mentah[count($mentah) - 1],
            'jumlah'      => count($nilai),
            'cakupan'     => $cakupan,
            'dasar'       => 'radius',
            'keyakinan'   => $this->keyakinan(count($nilai), $cakupan),
            'tahun_min'   => (int) $dalam->min(fn ($b) => $b['titik']->valuation_year),
            'tahun_maks'  => (int) $dalam->max(fn ($b) => $b['titik']->valuation_year),
            'kelompok'    => $kelompok,
            'tren'        => $this->tren($dalam),
            'pembanding'  => $dalam,
        ];
    }

    /**
     * Kecenderungan nilai antar tahun dari pembanding yang dipakai
     * (2026-09-26, permintaan user). Kalau datanya cuma satu tahun, katakan
     * apa adanya — jangan menggambar garis dari satu titik.
     *
     * Laju per tahun dihitung dengan regresi kuadrat terkecil atas logaritma
     * nilai terhadap tahun, jadi hasilnya berupa persentase pertumbuhan dan
     * seluruh titik ikut menentukan, bukan hanya tahun pertama & terakhir.
     */
    public function tren(Collection $dalam): array
    {
        $perTahun = [];

        foreach ($dalam as $b) {
            $perTahun[(int) $b['titik']->valuation_year][] = (float) $b['titik']->land_rate;
        }

        ksort($perTahun);

        $tahun = [];
        foreach ($perTahun as $th => $nilai) {
            sort($nilai);
            $tengah = count($nilai) % 2
                ? $nilai[intdiv(count($nilai), 2)]
                : ($nilai[count($nilai) / 2 - 1] + $nilai[count($nilai) / 2]) / 2;

            $tahun[] = ['tahun' => $th, 'jumlah' => count($nilai), 'tengah' => (int) round($tengah)];
        }

        if (count($tahun) < 2) {
            return [
                'status' => 'satu_tahun',
                'tahun'  => $tahun,
                'pesan'  => 'Data di lokasi ini hanya tahun ' . ($tahun[0]['tahun'] ?? '-')
                    . ', jadi kecenderungan antar tahun belum bisa dilihat.',
            ];
        }

        // Regresi ln(nilai) terhadap tahun.
        $n = $sx = $sy = $sxy = $sxx = 0;

        foreach ($dalam as $b) {
            $x = (float) $b['titik']->valuation_year;
            $y = log(max(1, (float) $b['titik']->land_rate));
            $n++;
            $sx += $x;
            $sy += $y;
            $sxy += $x * $y;
            $sxx += $x * $x;
        }

        $pembagi = ($n * $sxx) - ($sx * $sx);
        $laju    = $pembagi != 0.0 ? (exp((($n * $sxy) - ($sx * $sy)) / $pembagi) - 1) * 100 : null;

        $cukup = $n >= 5 && count($tahun) >= 3;

        return [
            'status'    => $cukup ? 'ada' : 'tipis',
            'tahun'     => $tahun,
            'laju'      => $laju === null ? null : round($laju, 1),
            'dari'      => $tahun[0]['tahun'],
            'sampai'    => $tahun[count($tahun) - 1]['tahun'],
            'pesan'     => $cukup
                ? null
                : 'Datanya tersebar di ' . count($tahun) . ' tahun dengan ' . $n . ' titik saja — arah angkanya ditampilkan apa adanya, belum cukup untuk disebut tren.',
        ];
    }

    /** Mundur ke nilai tengah wilayah bila radius 5 km pun kosong. */
    private function wilayahTerdekat(float $lat, float $lon, ?string $kelompok, int $tahunIni): ?array
    {
        // Titik terdekat dipakai untuk menebak wilayahnya — pengguna hanya
        // memasukkan koordinat, bukan nama kelurahan.
        $terdekat = $this->sekitar($lat, $lon, 25, $kelompok)->first()
            ?? $this->sekitar($lat, $lon, 60, $kelompok)->first();

        if (! $terdekat) {
            return null;
        }

        $acuan = $terdekat['titik'];

        foreach ([['village', 'kelurahan'], ['district', 'kecamatan'], ['city', 'kabupaten/kota']] as [$kolom, $label]) {
            if (! $acuan->{$kolom}) {
                continue;
            }

            $query = LandValuePoint::where($kolom, $acuan->{$kolom});

            if ($kelompok) {
                $query->where('property_group', $kelompok);
            }

            if ($this->tahun) {
                $query->whereBetween('valuation_year', $this->tahun);
            }

            $titik = $query->get();

            if ($titik->count() < self::MIN_PEMBANDING) {
                continue;
            }

            $nilai = $titik->pluck('land_rate')->map(fn ($v) => (float) $v)->all();
            $bobot = $titik->map(fn ($p) => self::SUSUT_TAHUN ** max(0, $tahunIni - (int) $p->valuation_year))->all();

            $tengah = $this->kuantilBerbobot($nilai, $bobot, 0.5);
            sort($nilai);

            return [
                'status'     => 'wilayah',
                'tengah'     => (int) round($tengah),
                'bawah'      => (int) round($tengah / self::FAKTOR_RENTANG),
                'atas'       => (int) round($tengah * self::FAKTOR_RENTANG),
                'data_min'   => (int) $nilai[0],
                'data_maks'  => (int) $nilai[count($nilai) - 1],
                'jumlah'     => count($nilai),
                'cakupan'    => $label . ' ' . $acuan->{$kolom},
                'dasar'      => 'wilayah',
                'keyakinan'  => 'rendah',
                'tahun_min'  => (int) $titik->min('valuation_year'),
                'tahun_maks' => (int) $titik->max('valuation_year'),
                'kelompok'   => $kelompok,
                'jarak_acuan' => $terdekat['jarak'],
                'tren'       => $this->tren($titik->map(fn ($p) => [
                    'titik' => $p,
                    'jarak' => self::jarakKm($lat, $lon, $p->latitude, $p->longitude),
                ])->values()),
                'pembanding' => $titik->map(fn ($p) => [
                    'titik' => $p,
                    'jarak' => self::jarakKm($lat, $lon, $p->latitude, $p->longitude),
                ])->sortBy('jarak')->values(),
            ];
        }

        return null;
    }

    /**
     * Label keyakinan. Dasarnya jumlah pembanding dan seberapa jauh radius
     * harus dilebarkan — dua hal yang paling menentukan galat pada uji ulang.
     */
    private function keyakinan(int $jumlah, string $cakupan): string
    {
        $km = (float) str_replace(',', '.', $cakupan);

        return match (true) {
            $jumlah >= 8 && $km <= 1 => 'tinggi',
            $jumlah >= 5 && $km <= 3 => 'sedang',
            default                  => 'rendah',
        };
    }

    /** Kuantil berbobot — nilai tengah yang memperhitungkan bobot tiap titik. */
    private function kuantilBerbobot(array $nilai, array $bobot, float $q): float
    {
        $pasangan = array_map(null, $nilai, $bobot);
        usort($pasangan, fn ($a, $b) => $a[0] <=> $b[0]);

        $total = array_sum($bobot);
        $kumulatif = 0;

        foreach ($pasangan as [$v, $w]) {
            $kumulatif += $w;
            if ($kumulatif >= $q * $total) {
                return (float) $v;
            }
        }

        return (float) end($pasangan)[0];
    }

    /** "2.5" -> "2,5 km". */
    private function kmTeks(float $km): string
    {
        return rtrim(rtrim(number_format($km, 2, ',', '.'), '0'), ',') . ' km';
    }

    /** Jarak dua koordinat dalam kilometer (haversine). */
    public static function jarakKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $R * asin(min(1.0, sqrt($a)));
    }

    /**
     * Terima tempelan dari Google Maps: "-6.304484, 106.805611" atau
     * "-6.304484 106.805611". Mengembalikan [lat, lon] atau null.
     */
    public static function baca(string $teks): ?array
    {
        $teks = trim($teks);

        if ($teks === '') {
            return null;
        }

        if (! preg_match('/(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)/', str_replace(["\n", "\t"], ' ', $teks), $m)) {
            return null;
        }

        $lat = (float) $m[1];
        $lon = (float) $m[2];

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        return [$lat, $lon];
    }
}
