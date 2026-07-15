<?php

return [
    'company_name'    => 'KJPP Sugianto Prasodjo dan Rekan',
    'company_address' => '18 OFFICE PARK LANTAI 3 UNIT A-3E Jalan Tahi Bonar Simatupang Nomor 18, Keluarahan Kebagusan, Kecamatan Pasar Minggu Jakarta Selatan DKI Jakarta 12520',
    'company_phone'   => '(021) 2270 8777',
    'company_email'   => 'kjppspr.jkt@gmail.com',
    'company_logo'    => public_path('images/logo_spr.png'),

    'bank_account' => [
        'bank_name'      => 'Bank Mandiri',
        'account_number' => '7301121479',
        'account_name'   => 'KJPP Sugianto Prasodjo dan Rekan',
    ],

    // Teks baku (klausul standar) dipisah dari controller supaya
    // gampang diedit tim legal tanpa deploy ulang kode.
    'standard_clauses' => [
        'independence' => 'Penilaian akan dilaksanakan secara independen dan objektif sesuai dengan Standar Penilaian Indonesia (SPI) yang berlaku.',
        'validity'     => 'Penawaran ini berlaku selama 30 (tiga puluh) hari kalender sejak tanggal diterbitkan.',
        'confidential' => 'Seluruh data dan informasi yang diberikan akan dijaga kerahasiaannya sesuai kode etik profesi penilai.',
    ],
];
