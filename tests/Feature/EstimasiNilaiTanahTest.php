<?php

namespace Tests\Feature;

use App\Models\LandValuePoint;
use App\Models\Role;
use App\Models\User;
use App\Services\EstimasiNilaiTanah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Estimasi rentang nilai tanah per m² dari titik koordinat (2026-09-26,
 * permintaan user).
 */
class EstimasiNilaiTanahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMINISTRATOR))->firstOrFail();
    }

    /** Sebar $jumlah titik di sekitar koordinat acuan. */
    private function titik(float $lat, float $lon, int $jumlah, int $rate, array $timpa = []): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            LandValuePoint::create(array_merge([
                // ~110 m per 0,001 derajat: semuanya tetap di dalam 0,5 km.
                'latitude'       => $lat + ($i * 0.0005),
                'longitude'      => $lon + ($i * 0.0005),
                'valuation_year' => 2024,
                'property_type'  => 'Rumah Tinggal',
                'property_group' => 'hunian',
                'land_rate'      => $rate,
                'city'           => 'Kota Uji',
                'district'       => 'Kec. Uji',
                'village'        => 'Kel. Uji',
            ], $timpa));
        }
    }

    public function test_membaca_koordinat_tempelan_google_maps(): void
    {
        $this->assertSame([-6.304484, 106.805611], EstimasiNilaiTanah::baca('-6.304484, 106.805611'));
        $this->assertSame([-6.304484, 106.805611], EstimasiNilaiTanah::baca(' -6.304484  106.805611 '));
        $this->assertNull(EstimasiNilaiTanah::baca('Jalan Bangau IV'));
        $this->assertNull(EstimasiNilaiTanah::baca(''));
        // Di luar rentang lintang/bujur yang sah.
        $this->assertNull(EstimasiNilaiTanah::baca('-999, 200'));
    }

    public function test_estimasi_memakai_titik_di_sekitar_koordinat(): void
    {
        $this->titik(-6.30, 106.80, 5, 10_000_000);
        // Titik jauh (>5 km) tidak boleh ikut menarik angkanya.
        $this->titik(-6.50, 106.80, 5, 1_000_000);

        $hasil = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80);

        $this->assertSame('ok', $hasil['status']);
        $this->assertSame(10_000_000, $hasil['tengah']);
        $this->assertSame(5, $hasil['jumlah']);
        $this->assertLessThan($hasil['tengah'], $hasil['bawah']);
        $this->assertGreaterThan($hasil['tengah'], $hasil['atas']);
    }

    public function test_saringan_jenis_properti_dihormati(): void
    {
        $this->titik(-6.30, 106.80, 4, 10_000_000);
        $this->titik(-6.30, 106.80, 4, 2_000_000, ['property_group' => 'industri', 'property_type' => 'Gudang']);

        $hunian = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80, 'hunian');
        $this->assertSame(10_000_000, $hunian['tengah']);
        $this->assertSame(4, $hunian['jumlah']);

        $industri = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80, 'industri');
        $this->assertSame(2_000_000, $industri['tengah']);
    }

    /** Tanpa pembanding di radius mana pun, jatuh ke nilai tengah wilayah. */
    public function test_mundur_ke_wilayah_bila_radius_kosong(): void
    {
        $this->titik(-6.60, 106.80, 4, 3_000_000);   // ~33 km: di luar radius 5 km

        $hasil = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80);

        $this->assertSame('wilayah', $hasil['status']);
        $this->assertSame('rendah', $hasil['keyakinan']);
        $this->assertStringContainsString('Kel. Uji', $hasil['cakupan']);
    }

    public function test_tanpa_data_sama_sekali_tidak_memaksa_angka(): void
    {
        $hasil = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80);

        $this->assertSame('kosong', $hasil['status']);
        $this->assertArrayNotHasKey('tengah', $hasil);
    }

    /** Data lama diberi bobot lebih kecil daripada data baru. */
    public function test_data_baru_lebih_berbobot(): void
    {
        $this->titik(-6.30, 106.80, 3, 20_000_000, ['valuation_year' => 2024]);
        $this->titik(-6.3005, 106.8005, 3, 5_000_000, ['valuation_year' => 2019]);

        $hasil = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80);

        $this->assertSame(20_000_000, $hasil['tengah']);
    }

    public function test_halaman_terbuka_dan_menampilkan_hasil(): void
    {
        $this->titik(-6.30, 106.80, 6, 12_500_000);

        $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80']))
            ->assertOk()
            ->assertSee('Rentang indikatif per m', false)
            ->assertSee('Rp12.500.000')
            ->assertSee('Pembanding terdekat');
    }

    /** Klik peta memanggil ?partial=1 dan hanya menerima panel hasil. */
    public function test_partial_mengembalikan_panel_saja(): void
    {
        $this->titik(-6.30, 106.80, 4, 7_500_000);

        $res = $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80', 'partial' => 1]))
            ->assertOk()
            ->assertSee('Rp7.500.000')
            ->assertSee('dataPeta', false);

        // Tanpa kerangka halaman: tidak ada <html>, peta, atau formulir.
        $isi = $res->getContent();
        $this->assertStringNotContainsString('<html', $isi);
        $this->assertStringNotContainsString('petaEstimasi', $isi);
        $this->assertStringNotContainsString('leaflet.js', $isi);
    }

    /** Halaman penuh tetap membawa peta walau belum ada koordinat. */
    public function test_peta_tampil_walau_belum_ada_koordinat(): void
    {
        $this->actingAs($this->admin)
            ->get(route('estimasi.index'))
            ->assertOk()
            ->assertSee('petaEstimasi', false)
            ->assertSee('js/leaflet/leaflet.js', false)
            ->assertSee('Klik titik mana pun di peta');
    }

    /** Radius diketik bebas; kosong berarti 5 km. */
    public function test_radius_bebas_diketik(): void
    {
        $service = app(EstimasiNilaiTanah::class);

        // Kosong: selalu 5 km.
        $this->assertSame([5.0], array_map('floatval', $service->tanggaRadius(null)));
        // Diisi: persis angka itu, tidak melebar & tidak berhenti lebih awal.
        $this->assertSame([2.5], array_map('floatval', $service->tanggaRadius(2.5)));
        $this->assertSame([12.0], array_map('floatval', $service->tanggaRadius(12)));

        // Titik 7 km dari koordinat: di luar 5 km bawaan, masuk bila radius 8 km.
        $this->titik(-6.363, 106.80, 4, 4_000_000);

        $this->assertSame('wilayah', $service->hitung(-6.30, 106.80)['status']);

        $lebar = $service->hitung(-6.30, 106.80, null, 8);
        $this->assertSame('ok', $lebar['status']);
        $this->assertSame('8 km', $lebar['cakupan']);

        $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80', 'radius' => 8]))
            ->assertOk()
            ->assertSee('8 km');
    }

    /** Peta sebaran ikut tampil beserta titik pembandingnya. */
    public function test_peta_sebaran_tampil_dengan_titik_pembanding(): void
    {
        $this->titik(-6.30, 106.80, 4, 8_000_000);

        $res = $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80']))
            ->assertOk()
            ->assertSee('Peta sebaran')
            ->assertSee('petaEstimasi', false)
            ->assertSee('js/leaflet/leaflet.js', false)
            // Kotak rincian + pilihan Peta/Satelit (2026-09-26, permintaan user).
            ->assertSee('petaIsi', false)
            ->assertSee('rincTutup', false)
            ->assertSee('Satelit', false);

        // Rincian titik dikirim ke peta, bukan hanya nilai untuk tabel.
        $this->assertStringContainsString('"rp":8000000', $res->getContent());
        $this->assertStringContainsString('"no":', $res->getContent());
    }

    /** Data satu tahun saja: dikatakan apa adanya, tidak digambar sebagai tren. */
    public function test_tren_tidak_dipaksa_bila_data_satu_tahun(): void
    {
        $this->titik(-6.30, 106.80, 4, 8_000_000, ['valuation_year' => 2022]);

        $hasil = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80);

        $this->assertSame('satu_tahun', $hasil['tren']['status']);
        $this->assertStringContainsString('hanya tahun 2022', $hasil['tren']['pesan']);

        $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80']))
            ->assertOk()
            ->assertSee('hanya tahun 2022');
    }

    /** Data beberapa tahun: laju per tahun dihitung dan dirinci per tahun. */
    public function test_tren_muncul_bila_data_menyebar_beberapa_tahun(): void
    {
        $this->titik(-6.3000, 106.8000, 2, 10_000_000, ['valuation_year' => 2020]);
        $this->titik(-6.3001, 106.8001, 2, 12_000_000, ['valuation_year' => 2022]);
        $this->titik(-6.3002, 106.8002, 2, 15_000_000, ['valuation_year' => 2024]);

        $hasil = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80);
        $tren  = $hasil['tren'];

        $this->assertSame('ada', $tren['status']);
        $this->assertSame(3, count($tren['tahun']));
        $this->assertSame(2020, $tren['dari']);
        $this->assertSame(2024, $tren['sampai']);
        // Naik dari 10 juta ke 15 juta dalam 4 tahun ~ +10,7%/tahun.
        $this->assertGreaterThan(9, $tren['laju']);
        $this->assertLessThan(12, $tren['laju']);

        $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80']))
            ->assertOk()
            ->assertSee('Kecenderungan antar tahun')
            ->assertSee('/tahun', false);
    }

    /** Dua tahun dengan titik sedikit: ditandai belum cukup untuk disebut tren. */
    public function test_tren_tipis_ditandai(): void
    {
        $this->titik(-6.3000, 106.8000, 2, 10_000_000, ['valuation_year' => 2021]);
        $this->titik(-6.3001, 106.8001, 1, 12_000_000, ['valuation_year' => 2023]);

        $tren = app(EstimasiNilaiTanah::class)->hitung(-6.30, 106.80)['tren'];

        $this->assertSame('tipis', $tren['status']);
        $this->assertStringContainsString('belum cukup', $tren['pesan']);
    }

    public function test_koordinat_salah_diberi_tahu_bukan_galat_500(): void
    {
        $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => 'depan indomaret']))
            ->assertOk()
            ->assertSee('Koordinat tidak terbaca', false);
    }

    /**
     * Sebaran data masih renggang: saringan jenis yang membuat pembanding
     * habis dilepas otomatis, dan pemakainya diberi tahu.
     */
    public function test_saringan_dilepas_bila_pembanding_habis(): void
    {
        $this->titik(-6.30, 106.80, 5, 9_000_000);   // semuanya hunian

        $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80', 'kelompok' => 'industri']))
            ->assertOk()
            ->assertSee('memakai')
            ->assertSee('semua jenis properti')
            ->assertSee('Rp9.000.000');
    }

    /** Saringan yang masih ada pembandingnya TIDAK boleh dilepas diam-diam. */
    public function test_saringan_dipertahankan_bila_pembanding_ada(): void
    {
        $this->titik(-6.30, 106.80, 4, 9_000_000);
        $this->titik(-6.3002, 106.8002, 4, 2_000_000, ['property_group' => 'industri', 'property_type' => 'Gudang']);

        $this->actingAs($this->admin)
            ->get(route('estimasi.index', ['koordinat' => '-6.30, 106.80', 'kelompok' => 'industri']))
            ->assertOk()
            ->assertSee('Rp2.000.000')
            ->assertDontSee('semua jenis properti');
    }

    public function test_halaman_kosong_saat_belum_ada_koordinat(): void
    {
        $this->actingAs($this->admin)
            ->get(route('estimasi.index'))
            ->assertOk()
            ->assertSee('Klik titik mana pun di peta');
    }

    public function test_tamu_tidak_bisa_membuka(): void
    {
        $this->get(route('estimasi.index'))->assertRedirect(route('login'));
    }

    /** Pemetaan Jenis Properti ke kelompok pembanding. */
    public function test_kelompok_properti(): void
    {
        $this->assertSame('tanah', LandValuePoint::kelompok('Tanah'));
        $this->assertSame('hunian', LandValuePoint::kelompok('Rumah Tinggal'));
        $this->assertSame('hunian', LandValuePoint::kelompok('Apartemen [Per Unit]'));
        $this->assertSame('komersial', LandValuePoint::kelompok('Ruko / Rukan'));
        $this->assertSame('komersial', LandValuePoint::kelompok('Kantor [Tanah Dan Bangunan]'));
        $this->assertSame('industri', LandValuePoint::kelompok('Gudang'));
        $this->assertSame('industri', LandValuePoint::kelompok('Pabrik'));
        $this->assertSame('lain', LandValuePoint::kelompok('SPBU'));
    }
}
