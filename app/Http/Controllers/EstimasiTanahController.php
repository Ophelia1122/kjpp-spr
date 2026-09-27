<?php

namespace App\Http\Controllers;

use App\Models\LandValuePoint;
use App\Exports\PembandingRadiusExport;
use App\Services\EstimasiNilaiTanah;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

/**
 * Estimasi rentang nilai pasar tanah per meter dari titik koordinat
 * (2026-09-26, permintaan user). Halaman ringan: satu formulir, satu kartu
 * hasil, dan daftar pembanding — tanpa peta dan tanpa ekspor.
 */
class EstimasiTanahController extends Controller
{
    public function index(Request $request, EstimasiNilaiTanah $estimasi)
    {
        $data = $request->validate([
            'koordinat' => 'nullable|string|max:120',
            // Radius diketik bebas (2026-09-26, permintaan user); kosong = 5 km.
            'radius'    => 'nullable|numeric|min:0.1|max:50',
            // Rentang tahun data (penggeser di formulir).
            'tahun_min' => 'nullable|integer|min:1990|max:2100',
            'tahun_max' => 'nullable|integer|min:1990|max:2100',
            // Sumber titik: data pembanding pasar atau objek penilaian KJPP.
            'sumber'    => 'nullable|string|in:' . implode(',', array_keys(LandValuePoint::TIPE_LABELS)),
            'kelas'     => 'nullable|string|in:' . implode(',', array_keys(LandValuePoint::KELAS_LABELS)),
            'tujuan'    => 'nullable|string|max:60',
        ]);

        $titik  = EstimasiNilaiTanah::baca((string) ($data['koordinat'] ?? ''));
        $hasil  = null;
        $galat  = null;

        if (($data['koordinat'] ?? '') !== '' && ! $titik) {
            $galat = 'Koordinat tidak terbaca. Tempel dari Google Maps, contoh: -6.304484, 106.805611';
        }

        $batasTahun = [
            (int) (LandValuePoint::min('valuation_year') ?: 2019),
            (int) (LandValuePoint::max('valuation_year') ?: (int) now()->year),
        ];

        $tahunMin = (int) ($data['tahun_min'] ?? $batasTahun[0]);
        $tahunMax = (int) ($data['tahun_max'] ?? $batasTahun[1]);

        if ($tahunMin > $tahunMax) {
            [$tahunMin, $tahunMax] = [$tahunMax, $tahunMin];
        }

        $tahun = [$tahunMin, $tahunMax] === $batasTahun ? null : [$tahunMin, $tahunMax];

        $lepasSaringan = false;

        if ($titik) {
            [$lat, $lon] = $titik;
            $radius = isset($data['radius']) ? (float) $data['radius'] : null;

            // Data pembanding pasar jadi bawaan (2026-09-27, permintaan user):
            // mencampurnya dengan kesimpulan penilaian membuat titik tengah
            // bergantung komposisi yang kebetulan ada di lokasi itu.
            $sumber = $data['sumber'] ?? LandValuePoint::TIPE_PEMBANDING;
            $kelas  = $data['kelas'] ?? null;
            $tujuan = $data['tujuan'] ?? null;

            $hasil = $estimasi->hitung($lat, $lon, null, $radius, $tahun, $sumber, $kelas, $tujuan);

            // Sebaran data masih renggang (2026-09-26): kalau saringan jenis
            // properti membuat pembandingnya habis, ulangi tanpa saringan dan
            // katakan apa adanya — lebih berguna daripada layar kosong.
            if (in_array($hasil['status'], ['kosong', 'wilayah'], true) && $kelas) {
                $tanpa = $estimasi->hitung($lat, $lon, null, $radius, $tahun, $sumber, null, $tujuan);

                if ($tanpa['status'] === 'ok') {
                    $hasil = $tanpa;
                    $lepasSaringan = true;
                }
            }
        }

        $data = [
            'titik'    => $titik,
            'hasil'    => $hasil,
            'galat'    => $galat,
            'lepasSaringan' => $lepasSaringan,
            'radius'   => $data['radius'] ?? null,
            'sumber'   => $data['sumber'] ?? LandValuePoint::TIPE_PEMBANDING,
            'kelas'    => $data['kelas'] ?? null,
            'tujuan'   => $data['tujuan'] ?? null,
            'daftarTujuan' => LandValuePoint::whereNotNull('purpose')
                ->selectRaw('purpose, count(*) n')->groupBy('purpose')
                ->orderByDesc('n')->pluck('purpose')->all(),
            'jumlahPerSumber' => LandValuePoint::selectRaw('data_type, count(*) as n')
                ->groupBy('data_type')->pluck('n', 'data_type')->all(),
            'totalTitik' => LandValuePoint::count(),
            'tahunData'  => $batasTahun,
            'tahunMin'   => $tahunMin,
            'tahunMax'   => $tahunMax,
        ];

        // Klik pada peta memanggil halaman ini dengan ?partial=1 lewat fetch,
        // lalu menukar isi panel saja (2026-09-26, permintaan user): peta tidak
        // ikut dimuat ulang sehingga posisi & perbesarannya tetap.
        if ($request->boolean('partial')) {
            return view('estimasi._hasil', $data);
        }

        return view('estimasi.index', $data);
    }

    /** Unduh pembanding dalam radius sebagai kertas kerja Excel. */
    public function exportExcel(Request $request, EstimasiNilaiTanah $estimasi)
    {
        $data = $request->validate([
            'koordinat' => 'required|string|max:120',
            'kelas'     => 'nullable|string|in:' . implode(',', array_keys(LandValuePoint::KELAS_LABELS)),
            'sumber'    => 'nullable|string|in:' . implode(',', array_keys(LandValuePoint::TIPE_LABELS)),
            'tujuan'    => 'nullable|string|max:60',
            'radius'    => 'nullable|numeric|min:0.1|max:50',
            'tahun_min' => 'nullable|integer|min:1990|max:2100',
            'tahun_max' => 'nullable|integer|min:1990|max:2100',
        ]);

        $titik = EstimasiNilaiTanah::baca($data['koordinat']);
        abort_unless($titik, 422, 'Koordinat tidak terbaca.');

        [$lat, $lon] = $titik;

        $tahun = isset($data['tahun_min'], $data['tahun_max'])
            ? [(int) $data['tahun_min'], (int) $data['tahun_max']]
            : null;

        $hasil = $estimasi->hitung(
            $lat,
            $lon,
            null,
            isset($data['radius']) ? (float) $data['radius'] : null,
            $tahun,
            $data['sumber'] ?? LandValuePoint::TIPE_PEMBANDING,
            $data['kelas'] ?? null,
            $data['tujuan'] ?? null,
        );

        abort_if($hasil['status'] === 'kosong', 404, 'Tidak ada pembanding di sekitar titik ini.');

        $nama = 'Data Pembanding ' . str_replace([',', ' '], ['', '_'], $data['koordinat'])
            . ' ' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new PembandingRadiusExport($hasil['pembanding'], [round($lat, 6), round($lon, 6)], $hasil['cakupan']),
            $nama,
        );
    }

    /** Halaman penjelasan: asal data, rumus, hasil kalibrasi, batasan. */
    public function cara()
    {
        return view('estimasi.cara', [
            'jumlah'    => LandValuePoint::selectRaw('data_type, count(*) as n')
                ->groupBy('data_type')->pluck('n', 'data_type')->all(),
            'tahunData' => [
                (int) (LandValuePoint::min('valuation_year') ?: 2019),
                (int) (LandValuePoint::max('valuation_year') ?: (int) now()->year),
            ],
        ]);
    }
}
