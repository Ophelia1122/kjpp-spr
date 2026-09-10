<?php

return [
    'company_name'    => 'KJPP SUGIANTO PRASODJO DAN REKAN',
    'company_tagline' => 'Public Appraisers & Consultants',
    'company_address' => '18 OFFICE PARK LANTAI 3 UNIT A-3E Jalan Tahi Bonar Simatupang Nomor 18, Keluarahan Kebagusan, Kecamatan Pasar Minggu Jakarta Selatan DKI Jakarta 12520',
    'company_phone'   => '(021) 2270 8777',
    'company_email'   => 'kjppspr.jkt@gmail.com',
    'company_logo'    => public_path('images/logo_spr.png'),

    // Font body dokumen proposal (.docx & hasil PDF-nya). Helvetica ≈ Arial
    // di Word/LibreOffice, jadi pakai 'Arial' untuk hasil yang identik.
    'pdf_font'        => 'Arial Narrow',
    'pdf_font_size'   => 11,   // ukuran font isi (pt); judul bab = +2

    // Path binary LibreOffice untuk konversi .docx -> .pdf (Word = master).
    // Lokal Windows: full path soffice.exe. Docker/Linux: cukup 'soffice'.
    'libreoffice_bin' => env('LIBREOFFICE_BIN', 'soffice'),

    // Izin & registrasi tingkat kantor (teks baku proposal).
    'izin_usaha_no'   => '2.15.0131',
    'kepmenkeu_no'    => '722/KM.1/2015 tanggal 09 September 2015',
    'sttd_ojk_no'     => 'S-859/PM.223/2015 tanggal 17 November 2015',
    'npwp'            => '02.837.345.4-017.000',

    // Tarif PPN yang berlaku (untuk perhitungan Biaya Jasa Penilaian).
    'ppn_rate'        => 0.11,

    // Penilai Publik penanggung jawab / penandatangan proposal (baku).
    'signatory' => [
        'name'           => 'Arief Rachman Setiady, S.M., M.M.',
        'title'          => 'Partner',
        'izin_pp_no'     => 'P-1.25.00690',
        'sk_menkeu_no'   => '185/MK/SJ/2025 tanggal 23 April 2025',
        'ojk_kep_no'     => 'KEP-324/KS.13/2026 tanggal 22 Mei 2026',
        'sttd_ojk_no'    => 'KEP-324/KS.13/2026',
        'mappi_no'       => '13-S-04682',
        'rmk_no'         => 'RMK-2017.01230',
        'klasifikasi'    => 'Klasifikasi Bidang Jasa Properti',
    ],

    // Fallback rekening bila tabel `banks` kosong (Batch 4: rekening sekarang
    // di tabel `banks` + BankSeeder). Selaras dengan bank default seeder.
    'bank_account' => [
        'bank_name'      => 'Bank Mandiri',
        'account_number' => '070-00-1324576-1',
        'account_name'   => 'KJPP Sugianto Prasodjo dan Rekan',
    ],

    // Blok footer alamat kantor — dicetak rata tengah di footer HALAMAN 1
    // proposal (halaman 2+ pakai gambar public/images/footer-proposal.png).
    // Tiap elemen array = satu baris.
    'footer' => [
        'lines' => [
            'Head Office: 18 Office Park 3rd floor Unit A-3E, Jl. TB Simatupang, No. 18 Jakarta 12520',
            'Telp. (021) 22708555, 22708666, 22708777 Fax (021) 22708288',
            'Website : www.kjpp-spr.co.id',
            'E-mail: admin.pusat@kjpp-spr.co.id ; sugiantodanrekan@yahoo.co.id',
            'Branch Office: Denpasar (PS), Makassar (P), Semarang (PS), Pontianak (PS),  Surabaya (PS), Bandung (P),',
            'Cirebon (PS), Lampung (P), Palembang (P), Sukoharjo-Solo (PS), Serang (P), Karawang (P), Manado (PS), Medan (PS)',
        ],
    ],

    // Teks baku klausul standar — dipertahankan untuk kompatibilitas,
    // tidak lagi dipakai (teks baku proposal sekarang di config/proposal_clauses.php).
    'standard_clauses' => [
        'independence' => 'Penilaian akan dilaksanakan secara independen dan objektif sesuai dengan Standar Penilaian Indonesia (SPI) yang berlaku.',
        'validity'     => 'Penawaran ini berlaku selama 30 (tiga puluh) hari kalender sejak tanggal diterbitkan.',
        'confidential' => 'Seluruh data dan informasi yang diberikan akan dijaga kerahasiaannya sesuai kode etik profesi penilai.',
    ],
];
