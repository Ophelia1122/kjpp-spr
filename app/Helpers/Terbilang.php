<?php

namespace App\Helpers;

class Terbilang
{
    private static array $angka = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
        'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh',
        'Sebelas',
    ];

    /**
     * Mengubah angka (mendukung nilai desimal, desimal akan dibulatkan)
     * menjadi teks terbilang Bahasa Indonesia, diakhiri "Rupiah".
     * Contoh: Terbilang::make(15000000) => "Lima Belas Juta Rupiah"
     */
    public static function make(float|int $number): string
    {
        $number = (int) round($number);

        if ($number < 0) {
            return 'Minus ' . self::convert(abs($number)) . ' Rupiah';
        }

        if ($number === 0) {
            return 'Nol Rupiah';
        }

        return trim(self::convert($number)) . ' Rupiah';
    }

    /**
     * Versi terbilang polos tanpa "Rupiah" — dipakai mis. untuk jumlah
     * hari kerja SLA di proposal: 7 => "tujuh", 14 => "empat belas".
     */
    public static function words(int $number): string
    {
        $number = abs($number);

        if ($number === 0) {
            return 'nol';
        }

        return mb_strtolower(trim(self::convert($number)));
    }

    private static function convert(int $number): string
    {
        if ($number < 12) {
            return self::$angka[$number];
        }

        if ($number < 20) {
            return self::convert($number - 10) . ' Belas';
        }

        if ($number < 100) {
            return self::convert(intdiv($number, 10)) . ' Puluh ' . self::convert($number % 10);
        }

        if ($number < 200) {
            return 'Seratus ' . self::convert($number - 100);
        }

        if ($number < 1000) {
            return self::convert(intdiv($number, 100)) . ' Ratus ' . self::convert($number % 100);
        }

        if ($number < 2000) {
            return 'Seribu ' . self::convert($number - 1000);
        }

        if ($number < 1_000_000) {
            return self::convert(intdiv($number, 1000)) . ' Ribu ' . self::convert($number % 1000);
        }

        if ($number < 1_000_000_000) {
            return self::convert(intdiv($number, 1_000_000)) . ' Juta ' . self::convert($number % 1_000_000);
        }

        if ($number < 1_000_000_000_000) {
            return self::convert(intdiv($number, 1_000_000_000)) . ' Miliar ' . self::convert($number % 1_000_000_000);
        }

        return self::convert(intdiv($number, 1_000_000_000_000)) . ' Triliun ' . self::convert($number % 1_000_000_000_000);
    }
}
