<?php

namespace App\Http\Controllers;

use App\Models\LandValuePoint;
use App\Services\EstimasiNilaiTanah;
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
            'kelompok'  => 'nullable|string|in:' . implode(',', array_keys(LandValuePoint::GROUPS)),
            'radius'    => 'nullable|numeric|in:0.5,1,2,3,5',
        ]);

        $titik  = EstimasiNilaiTanah::baca((string) ($data['koordinat'] ?? ''));
        $hasil  = null;
        $galat  = null;

        if (($data['koordinat'] ?? '') !== '' && ! $titik) {
            $galat = 'Koordinat tidak terbaca. Tempel dari Google Maps, contoh: -6.304484, 106.805611';
        }

        $lepasSaringan = false;

        if ($titik) {
            [$lat, $lon] = $titik;
            $radius = isset($data['radius']) ? (float) $data['radius'] : null;

            $hasil = $estimasi->hitung($lat, $lon, $data['kelompok'] ?? null, $radius);

            // Sebaran data masih renggang (2026-09-26): kalau saringan jenis
            // properti membuat pembandingnya habis, ulangi tanpa saringan dan
            // katakan apa adanya — lebih berguna daripada layar kosong.
            if (in_array($hasil['status'], ['kosong', 'wilayah'], true) && ! empty($data['kelompok'])) {
                $tanpa = $estimasi->hitung($lat, $lon, null, $radius);

                if ($tanpa['status'] === 'ok') {
                    $hasil = $tanpa;
                    $lepasSaringan = true;
                }
            }
        }

        return view('estimasi.index', [
            'titik'    => $titik,
            'hasil'    => $hasil,
            'galat'    => $galat,
            'lepasSaringan' => $lepasSaringan,
            'kelompok' => $data['kelompok'] ?? null,
            'radius'   => $data['radius'] ?? null,
            'totalTitik' => LandValuePoint::count(),
            'tahunData'  => [LandValuePoint::min('valuation_year'), LandValuePoint::max('valuation_year')],
        ]);
    }
}
