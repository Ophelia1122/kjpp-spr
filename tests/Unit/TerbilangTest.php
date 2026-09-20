<?php

namespace Tests\Unit;

use App\Helpers\Terbilang;
use PHPUnit\Framework\TestCase;

/**
 * Terbilang dipakai di proposal, invoice, dan kwitansi. Salah satu digit
 * saja membuat dokumen resmi salah, jadi angka penting dikunci di sini.
 */
class TerbilangTest extends TestCase
{
    public function test_angka_umum_dieja_benar(): void
    {
        $this->assertSame('Sepuluh Rupiah', Terbilang::make(10));
        $this->assertSame('Seratus Dua Puluh Lima Rupiah', Terbilang::make(125));
        $this->assertSame('Seribu Rupiah', Terbilang::make(1000));
    }

    public function test_nilai_besar_dieja_benar(): void
    {
        $this->assertSame('Lima Puluh Juta Rupiah', Terbilang::make(50_000_000));
        $this->assertSame('Empat Puluh Dua Juta Rupiah', Terbilang::make(42_000_000));
    }

    public function test_words_tanpa_kata_rupiah(): void
    {
        // words() sengaja huruf kecil: dipakai di tengah kalimat, mis.
        // "50% (lima puluh persen)".
        $this->assertSame('lima puluh', Terbilang::words(50));
        $this->assertSame('seratus', Terbilang::words(100));
    }

    public function test_tidak_ada_spasi_ganda(): void
    {
        foreach ([50_000_000, 1_250_000, 42_000_000, 7_500] as $angka) {
            $this->assertStringNotContainsString('  ', Terbilang::make($angka));
        }
    }
}
