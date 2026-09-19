<?php

/*
|--------------------------------------------------------------------------
| TEKS BAKU PROPOSAL PENAWARAN JASA PENILAIAN — KJPP SPR
|--------------------------------------------------------------------------
| Sumber tunggal (single source of truth) untuk seluruh KALIMAT BAKU
| ketentuan perusahaan. Dipakai oleh:
|   - App\Services\ProposalDocxBuilder  (generate .docx / master)
|   - (PDF di-generate dari .docx tsb via LibreOffice, jadi otomatis ikut)
|
| Placeholder pakai gaya :key dan di-replace via strtr() di builder.
| Struktur & logika kondisional (per jenis proposal) ada di builder,
| BUKAN di file ini — file ini murni teks supaya mudah diedit tim legal
| tanpa menyentuh kode.
*/

return [

    /*
    | GAYA TEKS OTOMATIS (di-render ProposalDocxBuilder::styleRuns()):
    |  - text_style.italic : istilah bahasa Inggris -> dicetak MIRING di mana pun
    |    muncul pada isi bab proposal.
    |  - text_style.bold   : nama sumber/standar (SPI, KEPI, dst) -> dicetak TEBAL
    |    (token akronim saja, angka kutipan setelahnya tidak).
    | Penebalan eksplisit tetap pakai markup **...** pada teks di bawah.
    */
    'text_style' => [
        'italic' => [
            'Market Value', 'Fair Value', 'Liquidation Value', 'Exposure Time',
            'Market Approach', 'Income Approach', 'Cost Approach',
            'Comprehensive Style Report', 'Short Form Report',
            'limited inspection', 'subsequent event',
            'hour meter', 'odometer', 'Counterpart',
        ],
        'bold' => ['SPI', 'KEPI', 'PSAK', 'POJK', 'PMK'],
    ],

    // Kalimat pembuka. Bagian :basis diisi MANUAL lewat field
    // "Dasar Permintaan Penilaian" di form proposal (projects.request_basis).
    'pembuka' =>
        'Sesuai dengan informasi permintaan penilaian :basis, mengenai permohonan jasa Penilai '
        . 'untuk melakukan penilaian aset milik :klien. Bersama ini kami Kantor Jasa Penilai Publik '
        . '(KJPP) SUGIANTO PRASODJO DAN REKAN mengajukan proposal biaya jasa Penilaian Aset dengan '
        . 'Lingkup Penugasan sebagai berikut:',
    'pembuka_basis_placeholder' =>
        'yang kami terima melalui ...................... permintaan penilaian tanggal ......................',

    // ------------------------------------------------------------------
    // 1. Penjelasan Status Penilai
    // ------------------------------------------------------------------
    // Markup **...** = tebal (di-render ProposalDocxBuilder; di-strip utk editor teks).
    'status_penilai' => [
        'Penilai Publik yang bertanda tangan di dalam Laporan Penilaian ini adalah **:nama, MAPPI (Cert.)** '
        . 'merupakan Penilai Publik Properti dengan Izin Penilai Publik **No. :izin** berdasarkan Surat '
        . 'Keputusan Menteri Keuangan Republik Indonesia Nomor **:sk_menkeu**, Penilai Publik juga telah '
        . 'terdaftar sebagai Profesi Penunjang Penilai Publik pada Sektor Jasa Keuangan sesuai dengan '
        . 'Keputusan Dewan Komisioner Otoritas Jasa Keuangan Nomor **:ojk_kep**, dengan lingkup pemberian '
        . 'jasa pada sektor Perbankan; Perasuransian, Penjaminan, dan Dana Pensiun; Lembaga Pembiayaan, '
        . 'Perusahaan Modal Ventura, Lembaga Keuangan Mikro, dan Lembaga Jasa Keuangan Lainnya; Inovasi '
        . 'Teknologi Sektor Keuangan serta Aset Keuangan Digital dan Aset Kripto.',

        'Penilai bertindak atas nama **KANTOR JASA PENILAI PUBLIK SUGIANTO PRASODJO DAN REKAN** memiliki '
        . '**Izin Usaha resmi** dari Kementerian Keuangan Republik Indonesia No. **:izin_usaha** berdasarkan '
        . '**Kepmenkeu No. :kepmenkeu** dari Menteri Keuangan Republik Indonesia. KJPP Sugianto Prasodjo '
        . 'dan Rekan adalah perusahaan penilai independen yang terdaftar di Masyarakat Profesi Penilai '
        . 'Indonesia (MAPPI) dan terdaftar di Otoritas Jasa Keuangan/OJK (d/h Bapepam-LK) berdasarkan '
        . '**Surat Tanda Terdaftar Profesi Penunjang Pasar Modal No. :sttd_ojk.**',

        'Sebagai Penilai kami dalam posisi untuk memberikan penilaian yang objektif dan tidak memihak. '
        . 'Kami sebagai penilai menyatakan bahwa status kami adalah sebagai penilai independen. Kami '
        . 'menyatakan bahwa tidak ada keterlibatan material dan benturan kepentingan baik yang aktual '
        . 'maupun bersifat potensial terhadap objek penilaian. Sebagai Penilai kami tegaskan kami '
        . 'memiliki kompetensi dalam melakukan penilaian atas objek penilaian termasuk seluruh Penilai, '
        . 'tenaga ahli dan staf pelaksana yang terlibat dalam proses penilaian yang dimaksud sehingga '
        . 'tidak memerlukan bantuan tenaga ahli dari luar.',
    ],

    // ------------------------------------------------------------------
    // 2 & 3. Identifikasi Pemberi Tugas / Pengguna Laporan
    // ------------------------------------------------------------------
    'pemberi_tugas_intro'   => 'Pemberi Tugas adalah :desc.',
    'pengguna_laporan_intro' => 'Pengguna Laporan adalah sebagai berikut:',
    'pengguna_laporan_lk_kap' =>
        'Kantor Akuntan Publik (KAP) / Auditor sebagai pihak yang melakukan audit atas laporan '
        . 'keuangan Perusahaan (:klien).',

    // ------------------------------------------------------------------
    // 4. Setelah tabel Obyek Penilaian
    // ------------------------------------------------------------------
    // Slot: :nama_sertifikat = daftar "Atas Nama" objek penilaian (form),
    //       :pemberi_tugas   = nama Pemberi Tugas.
    'post_objek_hubungan' =>
        'Nama yang tercantum dalam dokumen kepemilikan atas objek penilaian adalah :nama_sertifikat, '
        . 'sedangkan Pemberi Tugas dalam penugasan ini adalah :pemberi_tugas. Berdasarkan dokumen dan '
        . 'informasi yang disampaikan kepada Penilai, terdapat hubungan kepemilikan/penguasaan atas '
        . 'objek penilaian dimaksud oleh Pemberi Tugas. Penilai tidak melakukan verifikasi hukum secara '
        . 'independen terhadap hubungan hukum dimaksud dan menggunakan dokumen serta informasi yang '
        . 'disampaikan oleh Pemberi Tugas sebagai dasar dalam penugasan ini.',

    'post_objek' =>
        'Kami menegaskan bahwa seluruh pimpinan, rekan, dan staf KJPP Sugianto Prasodjo dan Rekan '
        . 'tidak bertanggung jawab atas kebenaran data dan informasi yang disampaikan oleh Pemberi '
        . 'Tugas atau pihak lain yang menjadi dasar penilaian ini. Segala bentuk informasi yang '
        . 'diberikan terkait objek penilaian sepenuhnya menjadi tanggung jawab Pengguna Laporan '
        . 'apabila pengambilan keputusan dilakukan tanpa didasari dokumen yang lengkap dan informasi '
        . 'yang jelas. Oleh karena itu, Penilai menyarankan agar Pengguna Laporan melakukan verifikasi '
        . 'secara menyeluruh terhadap seluruh dokumen dan informasi yang menjadi dasar penilaian '
        . 'sebelum mengambil keputusan berdasarkan laporan yang akan diterbitkan.',

    // ------------------------------------------------------------------
    // 5. Jenis Mata Uang
    // ------------------------------------------------------------------
    'mata_uang' => 'Jenis Mata Uang yang akan digunakan dalam laporan penilaian adalah Rupiah (Rp).',

    // ------------------------------------------------------------------
    // 6. Maksud & Tujuan (dipilih per jenis proposal oleh builder)
    // ------------------------------------------------------------------
    'maksud_pasar'  => 'Memberikan opini atas Nilai Pasar (Market Value) terhadap properti yang dinilai pada tanggal penilaian.',
    'maksud_lelang' => 'Memberikan opini atas Nilai Pasar (Market Value) dan Nilai Likuidasi (Liquidation Value) terhadap properti yang dinilai pada tanggal penilaian.',
    'maksud_wajar'  => 'Memberikan opini atas Nilai Wajar (Fair Value) terhadap properti yang dinilai pada tanggal penilaian.',

    'tujuan_jual_beli'      => 'Penilaian untuk tujuan Jual Beli untuk kepentingan :klien.',
    'tujuan_penjaminan'     => 'Penilaian untuk tujuan Penjaminan Utang pada :klien.',
    'tujuan_lelang'         => 'Penilaian untuk tujuan Lelang pada :klien.',
    'tujuan_lk'             =>
        'Penilaian ini dilakukan untuk tujuan Pelaporan Keuangan. Aset berupa :objek diklasifikasikan '
        . 'sebagai :psak sesuai dengan PSAK 216/240/202, dengan pengukuran nilai wajar mengacu pada PSAK 113.',

    // ------------------------------------------------------------------
    // 7. Dasar Nilai
    // ------------------------------------------------------------------
    'dasar_nilai_intro' => 'Sesuai dengan tujuan penilaian, Dasar Nilai yang digunakan adalah :dasar.',

    'def_nilai_pasar' =>
        'Nilai Pasar didefinisikan sebagai estimasi sejumlah uang yang dapat diperoleh atau dibayar '
        . 'untuk penukaran suatu aset atau liabilitas pada tanggal penilaian, antara pembeli yang '
        . 'berminat membeli dengan penjual yang berminat menjual, dalam suatu transaksi bebas ikatan, '
        . 'yang pemasarannya dilakukan secara layak, di mana kedua pihak masing-masing bertindak atas '
        . 'dasar pemahaman yang dimilikinya, kehati-hatian dan tanpa paksaan. '
        . '(SPI 101.3.1 - Nilai Pasar sebagai Dasar Nilai).',

    'def_nilai_wajar' =>
        'Nilai Wajar adalah harga yang akan diterima dari penjualan aset atau dibayarkan untuk '
        . 'pengalihan liabilitas dalam transaksi yang teratur diantara pelaku pasar pada tanggal '
        . 'pengukuran. (SPI 102.3.17 - Dasar Nilai selain Nilai Pasar:pojk).',
    'pojk28_suffix' => '; POJK 28/POJK.04/2021',

    'def_nilai_likuidasi' =>
        'Nilai Likuidasi adalah sejumlah uang yang mungkin diterima dari penjualan suatu aset dalam '
        . 'jangka waktu yang relatif pendek untuk dapat memenuhi jangka waktu pemasaran dalam definisi '
        . 'Nilai Pasar. Pada beberapa situasi, Nilai Likuidasi dapat melibatkan penjual yang tidak '
        . 'berminat menjual, dan pembeli yang membeli dengan mengetahui situasi yang tidak '
        . 'menguntungkan penjual. (SPI 102.3.5 - Dasar Nilai Selain Nilai Pasar).',

    'def_waktu_ekspos' =>
        'Waktu Ekspos (Exposure Time) adalah estimasi waktu dari suatu aset yang dinilai, dianggap '
        . 'telah ditawarkan dalam suatu pasar hipotesis untuk dijual sesuai definisi Nilai Pasar pada '
        . 'tanggal penilaian. Estimasi waktu (retrospektif) yang didasarkan suatu analisis kejadian '
        . 'masa lalu dengan asumsi adanya transaksi dalam pasar terbuka dan kompetitif. '
        . '(Pedoman Penilaian Indonesia - 05)',

    'pu_likuidasi_note' =>
        'Pada beberapa kasus, bank dapat meminta Penilai untuk memberikan opini Nilai Likuidasi pada '
        . 'saat proses pemberian kredit (penilaian untuk penjaminan utang) dan dasar nilai ini '
        . 'dinyatakan sebagai Indikasi Nilai Likuidasi. Indikasi nilai ini hanya merupakan estimasi '
        . 'awal yang tidak mengikat dan tidak dapat digunakan pada saat terjadi pelepasan kredit macet '
        . 'atau pengambilalihan aset jaminan oleh Bank. Pada umumnya Indikasi Nilai Likuidasi diperoleh '
        . 'dengan mengenakan diskon sebesar 20% sampai dengan 40% dari Nilai Pasar (Interpretasi SPI '
        . '102 Butir 3.5).',

    // ------------------------------------------------------------------
    // 8. Tanggal Penilaian
    // ------------------------------------------------------------------
    'tanggal_penilaian_umum' =>
        'Tanggal penilaian diartikan dalam SPI sebagai tanggal pada saat nilai dinyatakan dan '
        . 'diberlakukan. Tanggal ini berbeda dengan tanggal laporan penilaian yang akan diterbitkan '
        . 'atau tanggal dimana inspeksi akan dilakukan. Dalam penilaian ini tanggal penilaian '
        . 'ditetapkan berdasarkan tanggal terakhir inspeksi lapangan:tgl. Sehubungan dengan '
        . 'kemungkinan perubahan yang terjadi terhadap kondisi pasar dan kondisi aset/properti yang '
        . 'dinilai, maka laporan penilaian ini hanya dapat merepresentasikan tentang opini :dasar pada '
        . 'saat tanggal penilaian. Kami asumsikan tidak adanya kejadian luar biasa setelah tanggal '
        . 'penilaian yang dapat mempengaruhi hasil penilaian secara keseluruhan/bersifat signifikan '
        . 'terhadap aset/properti yang dinilai tersebut.',

    'tanggal_penilaian_lk' => [
        'Tanggal penilaian diartikan dalam SPI sebagai tanggal pada saat nilai dinyatakan dan '
        . 'diberlakukan. Tanggal ini berbeda dengan tanggal laporan penilaian yang akan diterbitkan '
        . 'atau tanggal dimana inspeksi akan dilakukan. Dalam penilaian ini tanggal penilaian '
        . 'ditetapkan berdasarkan tanggal pelaporan keuangan yaitu tanggal :tgl. Tanggal ini berbeda '
        . 'dari tanggal penyelesaian laporan penilaian maupun tanggal inspeksi lapangan.',

        'Sehubungan dengan kemungkinan perubahan yang terjadi terhadap kondisi pasar dan kondisi '
        . 'aset/properti yang dinilai, maka laporan penilaian ini hanya dapat merepresentasikan tentang '
        . 'opini Nilai Wajar pada saat tanggal penilaian. Kami asumsikan tidak adanya kejadian luar '
        . 'biasa (subsequent event) setelah tanggal penilaian yang dapat mempengaruhi hasil penilaian '
        . 'secara keseluruhan/bersifat signifikan terhadap aset/properti yang dinilai tersebut.',
    ],

    // ------------------------------------------------------------------
    // 9. Tingkat Kedalaman Investigasi
    // ------------------------------------------------------------------
    'tki_intro' => 'Penilaian ini dilakukan dengan batasan investigasi sebagai berikut:',
    // Slot :alasan_limited pada butir "limited inspection".
    'tki_alasan_limited_placeholder' => '----sebutkan alasannya----',
    'tki_items' => [
        'Data dan informasi atas objek penilaian dan kelengkapannya kami peroleh dari pemberi tugas '
        . 'dan/atau pemilik aset/properti serta pihak terkait lainnya. Pemberi Tugas dan Pengguna '
        . 'Laporan memberikan pembebasan tanggung jawab apapun kepada KJPP Sugianto Prasodjo dan Rekan '
        . 'apabila data dan informasi tersebut tidak benar/tidak sesuai dengan fakta sebenarnya.',
        'Investigasi dilakukan melalui proses pengumpulan data dengan cara inspeksi, penelaahan, '
        . 'perhitungan dan analisis.',
        'Inspeksi dilakukan dengan disertai surat tugas atau persetujuan inspeksi serta dilengkapi '
        . 'berita acara inspeksi yang ditandatangani oleh Pemberi Tugas dan/atau pemilik objek atau '
        . 'pihak yang dikuasakan.',
        'Kami akan melakukan verifikasi terhadap keseluruhan atau bagian dari objek penilaian, yang '
        . 'diperlukan dan dianggap penting dalam pelaksanaan penilaian. Bila diketahui '
        . 'pemeriksaan/verifikasi tidak dapat dilakukan atau memiliki keterbatasan, maka hal-hal '
        . 'tersebut akan dinyatakan dalam berita acara perubahan Surat Perjanjian Kerja (SPK) yang '
        . 'ditandatangani oleh Penilai, Pemberi Tugas dan/atau Pemilik Objek Penilaian.',
        'Penilaian ini dilakukan dengan tingkat kedalaman investigasi yang terbatas (limited '
        . 'inspection), di mana penilai tidak melakukan pemeriksaan fisik secara menyeluruh terhadap '
        . 'seluruh bagian aset karena :alasan_limited. Data dan informasi yang digunakan dalam '
        . 'penilaian ini sebagian besar bersumber dari keterangan Pemberi Tugas atau pihak terkait, '
        . 'serta dokumen pendukung yang disampaikan kepada penilai, dan tidak diverifikasi secara '
        . 'independen kecuali dinyatakan lain. Oleh karena itu, nilai yang disajikan dalam laporan ini '
        . 'diasumsikan mencerminkan kondisi aset sebagaimana diinformasikan dan diamati secara visual, '
        . 'serta tidak memperhitungkan potensi kerusakan tersembunyi, cacat internal, atau '
        . 'ketidaksesuaian data yang tidak dapat diidentifikasi dalam penilaian terbatas ini.',
        'Bila ditemukan adanya batasan tingkat kedalaman investigasi, maka inspeksi kami lakukan '
        . 'secara sampling. Sedangkan jika aset yang di inspeksi dinformasikan tidak dapat diperiksa, '
        . 'maka hal tersebut akan dicatatkan sebagai kondisi pembatas yang berhubungan dengan asumsi '
        . 'khusus.',
        'Dalam hal terdapat perbedaan objek penilaian atau item lainnya yang dinyatakan dalam Surat '
        . 'Perjanjian Kerja (SPK) dengan hasil investigasi, maka perbedaan tersebut akan dinyatakan '
        . 'dalam berita acara perubahan Surat Perjanjian Kerja (SPK) yang ditandatangani oleh Penilai, '
        . 'Pemberi Tugas dan/atau Pemilik Objek Penilaian.',
        'Apabila inspeksi lapangan tidak dapat dilakukan karena keberadaan objek penilaian tidak '
        . 'diketahui, maka penugasan dimaksud tidak dapat diteruskan atau batal.',
        'Penilaian ini menggunakan luas tanah sebagaimana tercantum dalam sertipikat tanah. Penilai '
        . 'hanya melakukan verifikasi terbatas pada bagian tertentu yang dapat diidentifikasi secara '
        . 'fisik, tanpa melakukan pengukuran atas keseluruhan tanah dimaksud. Dengan demikian, '
        . 'kebenaran luas tanah sepenuhnya menjadi tanggung jawab Pemberi Tugas/Pemilik.',
    ],
    // Blok TKI tambahan per jenis aset (muncul mengikuti kategori objek
    // yang ada di proposal). Masing-masing = daftar paragraf.
    'tki_bangunan' => [
        'Penilaian atas spesifikasi bangunan didasarkan semata-mata pada hasil observasi visual yang '
        . 'dapat diamati secara langsung. Penilai tidak melakukan pemeriksaan teknis maupun pengujian '
        . 'laboratorium terhadap pondasi, struktur, ataupun tingkat kekerasan dinding bangunan. '
        . 'Penilaian ini dilakukan dengan metode observasi non-destruktif (penilai tidak melakukan uji '
        . 'material, uji struktur, maupun penggalian pondasi). Dengan demikian, kondisi struktural '
        . 'bangunan berada di luar tanggung jawab penilai.',
    ],
    'tki_mesin' => [
        'Penilaian mesin dan peralatan, kami mengambil dasar penilaian berdasarkan pemeriksaan visual '
        . '(non-destruktif) terhadap kondisi fisik aset pada saat inspeksi. Pemeriksaan mencakup '
        . 'identifikasi jenis, kapasitas, merek, model, nomor seri, tahun pembuatan, kondisi umum, dan '
        . 'kelengkapan komponen utama.',
        'Penilai tidak melakukan pembongkaran, uji fungsi, uji performa, atau pengujian destruktif '
        . 'terhadap mesin maupun komponennya, serta tidak melakukan pengujian laboratorium terhadap '
        . 'material, pelumas, atau cairan sistemik yang mungkin digunakan oleh mesin tersebut.',
        'Informasi tambahan seperti tahun instalasi, umur pakai, histori perawatan, jam operasi, dan '
        . 'status operasional (berfungsi/tidak berfungsi) diperoleh dari keterangan pengguna atau '
        . 'dokumen pendukung yang disediakan oleh Pemberi Tugas, dan tidak diverifikasi secara '
        . 'independen kecuali dinyatakan lain.',
    ],
    'tki_kendaraan' => [
        'Dalam penilaian kendaraan, penilai mengambil dasar penilaian berdasarkan pemeriksaan visual '
        . '(non-destruktif) terhadap kondisi fisik kendaraan pada saat inspeksi. Pemeriksaan mencakup '
        . 'identifikasi jenis, merek, model, nomor rangka, nomor mesin, tahun pembuatan, kapasitas '
        . 'mesin, kondisi umum, serta kelengkapan komponen dan perlengkapan standar kendaraan.',
        'Penilai tidak melakukan pembongkaran, uji fungsi, uji performa mesin, atau pengujian '
        . 'destruktif terhadap kendaraan dan komponennya, serta tidak melakukan pengujian laboratorium '
        . 'terhadap pelumas, bahan bakar, atau cairan sistemik yang digunakan oleh kendaraan tersebut.',
        'Informasi tambahan seperti tahun perakitan, jarak tempuh (odometer), histori perawatan, '
        . 'status operasional (berfungsi/tidak berfungsi), serta kelengkapan dokumen (STNK, BPKB, '
        . 'faktur pembelian, dan bukti servis) diperoleh dari pihak pengguna atau Pemberi Tugas, dan '
        . 'tidak diverifikasi secara independen kecuali dinyatakan lain.',
        'Dalam hal kendaraan tidak dapat dihidupkan atau diuji jalan pada saat inspeksi, penilai '
        . 'berasumsi bahwa kondisi mesin, transmisi, dan sistem kelistrikan sesuai dengan informasi '
        . 'yang diberikan oleh pihak terkait dan dalam kondisi fungsional wajar.',
        'Penilai tidak bertanggung jawab atas cacat tersembunyi, kerusakan internal, atau '
        . 'ketidaksesuaian spesifikasi teknis yang tidak dapat diidentifikasi melalui pemeriksaan '
        . 'visual.',
    ],
    'tki_alat_berat' => [
        'Dalam penilaian alat berat, penilai melakukan pemeriksaan visual (non-destruktif) untuk '
        . 'menilai kondisi fisik unit pada saat inspeksi. Pemeriksaan mencakup identifikasi jenis '
        . 'alat, merek, model, nomor seri, nomor rangka, kapasitas kerja, tahun pembuatan, kondisi '
        . 'umum, serta kelengkapan komponen utama (mesin, undercarriage, sistem hidrolik, '
        . 'boom/arm/bucket, dan kabin operator).',
        'Penilai tidak melakukan uji beban, uji fungsi hidrolik, uji performa mesin, atau pengujian '
        . 'destruktif, serta tidak melakukan analisis laboratorium terhadap oli, pelumas, cairan '
        . 'hidrolik, atau bahan bakar yang digunakan pada unit alat berat tersebut.',
        'Informasi tambahan seperti jam operasi (hour meter), histori perawatan, status operasional '
        . '(aktif/non-aktif), lokasi penggunaan, dan kepemilikan diperoleh dari keterangan pihak '
        . 'pengguna atau dokumen yang disediakan oleh Pemberi Tugas, dan tidak diverifikasi secara '
        . 'independen kecuali dinyatakan lain.',
        'Dalam hal alat berat tidak beroperasi pada saat inspeksi, penilai berasumsi bahwa kondisi '
        . 'internal mesin, sistem hidrolik, dan sistem transmisi sesuai dengan informasi yang '
        . 'diberikan oleh pihak terkait dan dalam kondisi fungsional wajar.',
        'Penilai tidak bertanggung jawab atas kerusakan internal, cacat tersembunyi, atau kegagalan '
        . 'fungsi sistem hidrolik dan mekanik yang tidak dapat diidentifikasi melalui pemeriksaan '
        . 'visual.',
    ],

    // ------------------------------------------------------------------
    // 10. Sifat dan Sumber Informasi
    // ------------------------------------------------------------------
    'sifat_sumber' =>
        'Informasi dan data yang relevan namun tidak membutuhkan verifikasi, data disetujui untuk '
        . 'digunakan sepanjang sumber data tersebut dipublikasikan pada tingkat nasional maupun '
        . 'internasional. Sumber data tersebut antara lain pada Badan Pertanahan Nasional, Bank '
        . 'Indonesia, Bursa Efek Indonesia dan Negara Lain, Data Pemerintah Kota termasuk Badan Pusat '
        . 'Statistik (BPS), Asosiasi Profesi Penilai di Indonesia maupun di luar negeri, sumber '
        . 'lainnya yang dapat dipercaya.',

    // ------------------------------------------------------------------
    // 11. Asumsi Umum dan Asumsi Khusus
    // ------------------------------------------------------------------
    'asumsi_intro' =>
        'Asumsi umum adalah hal yang wajar untuk di terima sebagai fakta dalam kontek perusahaan '
        . 'penilaian tanpa penyelidikan tertentu atau verifikasi, hal tersebut di nyatakan untuk dapat '
        . 'diterima dalam pemahaman penilaian. Asumsi dalam penilaian ini adalah sebagai berikut:',
    'asumsi_items' => [
        'Properti yang ditunjukkan kepada kami adalah benar merupakan properti dalam penilaian.',
        'Objek penilaian didukung dokumen yang sah menurut peraturan perundang-undangan yang '
        . 'membuktikan hak kepemilikan/penguasaan atau hak manfaat, dapat dialihkan/dibebani sesuai '
        . 'ketentuan, serta bebas dari sengketa, atau klaim pihak ketiga kecuali yang secara tegas '
        . 'diungkapkan kepada Penilai. Penilai tidak melakukan audit legal; keabsahan dokumen menjadi '
        . 'tanggung jawab Pemberi Tugas.',
        'Properti dimaksud dilengkapi dengan dokumen atas hak kepemilikan/penguasaan tanah yang sah '
        . 'secara hukum, dapat dialihkan dan bebas dari ikatan, tuntutan atau halangan apapun.',
        'Nilai yang dicantumkan dalam laporan ini serta setiap nilai lain dalam laporan yang '
        . 'merupakan bagian dari properti yang dinilai hanya berlaku sesuai dengan maksud dan tujuan '
        . 'penilaian. Nilai yang digunakan dalam laporan penilaian ini tidak boleh digunakan untuk '
        . 'tujuan penilaian lain yang dapat mengakibatkan terjadinya kesalahan.',
        'Objek penilaian yang terdapat di luar identifikasi secara sampling diasumsikan adalah benar, '
        . 'mendekati karakteristik yang sama dengan objek yang diperiksa secara sampling.',
        'Perbedaan kondisi yang mungkin terjadi antara tanggal penilaian dengan waktu penggunaan '
        . 'hasil penilaian dapat menurunkan relevansi opini nilai terhadap kebutuhan pengguna hasil '
        . 'penilaian, dikarenakan adanya perbedaan akses data dan informasi serta asumsi dan analisis '
        . 'penilaian. Apabila pengguna hasil penilaian menemukan kondisi tersebut, disarankan untuk '
        . 'menugaskan Penilai melakukan review terhadap penugasan yang telah dilaksanakan dan apabila '
        . 'dimungkinkan dan dibutuhkan, Penilai dapat melakukan penilaian ulang dengan mengulang '
        . 'kembali prosedur penilaian yang sebelumnya dilakukan, secara lebih lengkap. Proses dan '
        . 'prosedur tersebut harus dituangkan dalam penugasan yang berdiri sendiri dan berbeda dengan '
        . 'penugasan penilaian sebelumnya.',
    ],
    // Muncul hanya bila objek mengandung bangunan.
    'asumsi_bangunan_item' =>
        'Bagian-bagian bangunan yang tidak terlihat seperti struktur, pondasi dan tingkat kekerasan '
        . 'dinding terpenuhi sebagaimana mestinya dan berfungsi dengan baik.',
    'asumsi_khusus' => [
        'Asumsi Khusus adalah asumsi yang berbeda dari fakta yang sebenarnya pada tanggal penilaian '
        . 'atau hal yang tidak akan diberatkan oleh sebagian kecil pelaku pasar dalam suatu transaksi '
        . 'pada tanggal penilaian. Asumsi yang digunakan untuk penilaian aset ini sesuai dengan KEPI & '
        . 'SPI Edisi VII - 2018 dan Edisi Revisi Tahun 2020.',
        'Asumsi Khusus yang diperlukan dalam penilaian ini akan ditetapkan dan dirumuskan setelah '
        . 'Penilai melakukan inspeksi lapangan serta memperoleh data pendukung yang relevan. Asumsi '
        . 'Khusus tersebut akan diungkapkan secara jelas dalam Laporan Penilaian.',
    ],

    // ------------------------------------------------------------------
    // 12. Persyaratan atas Persetujuan untuk Publikasi
    // ------------------------------------------------------------------
    'publikasi' =>
        'Hasil Laporan penilaian dan/atau referensi yang melampirinya hanya ditujukan untuk pemberi '
        . 'tugas dan pengguna laporan. Kami tidak bertanggung jawab kepada pihak ketiga, dan baik '
        . 'sebagian maupun keseluruhan laporan atau rujukan terhadap laporan ini tidak dibenarkan '
        . 'untuk diterbitkan dalam dokumen apapun, pernyataan, edaran, ataupun untuk dikomunikasikan '
        . 'kepada pihak ketiga tanpa persetujuan tertulis terlebih dahulu dari kami untuk format '
        . 'maupun konteks di mana akan dimunculkan.',

    // ------------------------------------------------------------------
    // 13. Konfirmasi berdasarkan SPI
    // ------------------------------------------------------------------
    'konfirmasi_spi' =>
        'Penilaian ini dilakukan berdasarkan Kode Etik (KEPI) & Standar Penilaian Indonesia (SPI) '
        . 'Edisi VII Tahun 2018 dan SPI Edisi VII Revisi Tahun 2020 :spi_code sesuai ketentuan yang '
        . 'berlaku dan menjadi bagian yang tidak terpisahkan dari standar penilaian yang digunakan.',
    'spi_code_default' => 'SPI-300 (Real Properti)',

    // ------------------------------------------------------------------
    // 14. Laporan Penilaian
    // ------------------------------------------------------------------
    // Bab 14 = daftar huruf a–d. Markup **...** = tebal.
    //   a = laporan_intro, b = laporan_draft, c = laporan_final (+ Struktur),
    //   d = laporan_rangkap.
    'laporan_intro' =>
        'Jenis **laporan** penilaian yang akan disampaikan adalah **:style**, disusun dengan menggunakan '
        . 'Bahasa Indonesia. Jangka waktu pelaksanaan Investigasi dan penyusunan laporan penilaian '
        . 'adalah jangka waktu **:total** hari kerja dihitung sejak tanggal inspeksi terakhir dan tanggal '
        . 'penerimaan data terakhir sebagaimana tercatat dalam korespondensi (email/surat) atau daftar '
        . 'serah terima data dari Pemberi Tugas, dengan waktu pengerjaan sebagai berikut:',
    'laporan_draft' =>
        '**Laporan Draft / Resume Penilaian** dalam waktu **:draft** hari kerja setelah inspeksi Lapangan dan '
        . 'penerimaan data terakhir dimaksud.',
    'laporan_final' =>
        '**Laporan Final** akan disampaikan dalam waktu **:final** hari kerja setelah laporan resume/draft '
        . 'penilaian disetujui.',
    'laporan_struktur_intro' => '**Struktur Laporan Penilaian** meliputi:',
    'laporan_struktur' => [
        'Pendahuluan',
        'Lingkup Penugasan',
        'Data Penilaian',
        'Proses Penilaian',
        'Lampiran-lampiran termasuk foto, lokasi obyek penilaian dan lainnya',
    ],
    'laporan_rangkap' => '**Laporan Penilaian** akan disampaikan masing-masing lokasi dalam **2 (dua)** rangkap.',
    'style_long'  => 'Laporan Penilaian Terinci (Comprehensive Style Report)',
    'style_short' => 'Laporan Penilaian Ringkas (Short Form Report)',

    // ------------------------------------------------------------------
    // 15. Batasan Tanggung Jawab
    // ------------------------------------------------------------------
    'batasan_tanggung_jawab' =>
        'Sepanjang sesuai dengan ketentuan peraturan perundang-undangan yang berlaku, Penilai tidak '
        . 'mempunyai kewajiban maupun tanggung jawab kepada pihak manapun selain Pemberi '
        . 'Tugas/Pengguna Laporan yang ditetapkan.',

    // ------------------------------------------------------------------
    // 16. Pernyataan Kebenaran Data
    // ------------------------------------------------------------------
    'kebenaran_data_intro' => 'Pemberi tugas menyatakan bahwa:',
    'kebenaran_data_items' => [
        'Kami menerima data-data penilaian berupa; salinan legalitas tanah berupa copy sertifikat dan '
        . 'copy pajak bumi dan bangunan (PBB), copy Izin Mendirikan Bangunan (IMB)/Persetujuan '
        . 'Bangunan Gedung (PBG) dan dokumen lainnya yang terkait langsung objek penilaian.',
        'Seluruh informasi dan pernyataan baik secara lisan maupun tulisan serta dokumen baik dalam '
        . 'bentuk asli, foto copy dan/atau salinan yang kami sampaikan kepada KJPP Sugianto Prasodjo '
        . 'dan Rekan yang kemudian dituangkan dalam bentuk laporan adalah benar-benar berasal dari '
        . 'pemberi tugas, akurat, lengkap dan sesuai dengan keadaan yang sebenarnya serta tidak '
        . 'mengalami perubahan lagi sampai dengan dikeluarkannya laporan tersebut.',
        'Jika terjadi kesalahan penyampaian informasi atas dokumen baik dalam bentuk asli maupun foto '
        . 'copy, pernyataan dan keterangan baik lisan maupun tertulis dari Pemberi Tugas yang '
        . 'menyebabkan kesalahan dalam analisa dan perhitungan penilaian; maka laporan penilaian '
        . 'menjadi tidak berlaku dan KJPP Sugianto Prasodjo dan Rekan beserta pimpinan, seluruh rekan '
        . 'dan staf baik yang bertandatangan maupun yang tidak bertandatangan di dalam laporan '
        . 'penilaian dibebaskan dari tuntutan perdata dan/atau pidana atas kerugian yang timbul baik '
        . 'secara langsung maupun tidak langsung.',
        'Pemberi Tugas wajib dan bersedia memberikan surat pernyataan tertulis yang memuat substansi '
        . 'sebagaimana disebutkan di atas, yang dibuat terpisah dari surat penawaran ini serta '
        . 'ditandatangani di atas meterai yang berlaku.',
    ],

    // ------------------------------------------------------------------
    // 17. Pendekatan yang Digunakan
    // ------------------------------------------------------------------
    'pendekatan_intro' =>
        'Pendekatan yang akan digunakan dalam penilaian ini adalah salah satu atau dua dari '
        . 'pendekatan yang ada sesuai dengan data-data / atau yang ada di lapangan, diantaranya:',
    'pendekatan_items' => [
        ['lead' => 'Pendekatan Pasar (Market Approach)', 'text' =>
            'Pendekatan Pasar memberikan indikasi nilai dengan membandingkan aset dengan aset lainnya '
            . 'yang identik atau sebanding dimana terdapat informasi harga. '
            . '(SPI 106 3.12 - Pendekatan dan Metode Penilaian)'],
        ['lead' => 'Pendekatan Pendapatan (Income Approach)', 'text' =>
            'Pendekatan Pendapatan memberikan indikasi nilai dengan mengkonversi arus kas masa depan '
            . 'menjadi satu nilai saat ini. Pada Pendekatan Pendapatan, nilai aset ditentukan dengan '
            . 'referensi kepada pendapatan arus kas atau penghematan biaya yang dihasilkan aset. '
            . '(SPI 106 3.11 - Pendekatan dan Metode Penilaian)'],
        ['lead' => 'Pendekatan Biaya (Cost Approach)', 'text' =>
            'Pendekatan Biaya memberikan indikasi nilai menggunakan prinsip ekonomi bahwa pembeli '
            . 'akan membayar aset tidak lebih dari biaya untuk mendapatkan aset dengan utilitas yang '
            . 'sama, baik melalui pembelian atau dengan pembuatan konstruksi dengan mengecualikan '
            . 'factor-faktor seperti waktu yang tidak semestinya, ketidaknyamanan, risiko atau '
            . 'factor-faktor lainnya. (SPI 106 3.10 - Pendekatan dan Metode Penilaian)'],
    ],

    // ------------------------------------------------------------------
    // 18. Kondisi Pembatas Penilaian
    // ------------------------------------------------------------------
    'kondisi_pembatas' =>
        'Berdasarkan identifikasi awal, kondisi pembatas dalam pelaksanaan penilaian ini meliputi '
        . 'keterbatasan pada tujuan dan ruang lingkup penugasan, serta terbatasnya verifikasi fisik '
        . 'objek penilaian. Penilaian ini tidak mencakup uji teknis detail dan hanya didasarkan pada '
        . 'data/informasi yang tersedia, dokumen yang disampaikan oleh Pemberi Tugas, serta hasil '
        . 'inspeksi lapangan.',

    // ------------------------------------------------------------------
    // LAMPIRAN (di akhir dokumen, setelah tanda tangan) — REV.1
    // ------------------------------------------------------------------
    'lampiran_title' => 'LAMPIRAN PERMINTAAN DATA - DATA',
    'data_diperlukan_intro' => 'Data - data yang diperlukan :',
    'data_diperlukan_lk_extra' => 'List Objek Penilaian sesuai dengan Laporan Keuangan.',
    'data_diperlukan_items' => [
        'Copy legalitas / sertifikat tanah (lembaran lengkap sesuai aslinya)',
        'Copy IMB/PBG',
        'Copy PBB (NJOP Tahun Terakhir)',
        'Gambar Lay Out Bangunan',
        'Copy NPWP',
        'Surat Pernyataan Kebenaran Data yang merupakan bagian yang tidak terpisahkan dari kontrak kerja.',
    ],

    // ------------------------------------------------------------------
    // 20. Prosedur Pelaksanaan Penugasan
    // ------------------------------------------------------------------
    'prosedur_intro' =>
        'Penugasan penilaian ini akan dilakukan menurut prosedur dengan tahapan-tahapan sebagai berikut :',
    'prosedur_items' => [
        'Pengumpulan data awal.',
        'Pemeriksaan dan penelitian lapangan, untuk memperoleh data akurat tentang spesifikasi dan '
        . 'kondisi sebenarnya dari obyek penugasan.',
        'Penentuan kondisi terlihat, guna menentukan kondisi obyek penilaian.',
        'Penentuan pendekatan penilaian yang akan digunakan.',
        'Penetapan nilai aset dan penyampaian resume penilaian.',
        'Penyusunan laporan final penilaian properti.',
    ],

    // ------------------------------------------------------------------
    // 21-24. Pembatalan / Kerahasiaan / Pendamping / Berita Acara
    // ------------------------------------------------------------------
    'pembatalan' =>
        'Pembatalan penugasan secara sepihak oleh Pemberi Tugas tidak membebaskan Pemberi Tugas dari '
        . 'kewajiban-kewajiban terhadap Penilai. Tujuan penugasan tidak memiliki hubungan ataupun '
        . 'kepentingan dengan pekerjaan yang dilakukan oleh Penilai dan tidak dapat dijadikan alasan '
        . 'oleh Pemberi Tugas untuk pembatalan penugasan. Apabila inspeksi lapangan tidak dapat '
        . 'dilakukan karena keberadaan objek penilaian tidak diketahui, maka biaya-biaya yang timbul '
        . 'karena proses inspeksi yang batal tersebut tetap kami perhitungkan.',
    'kerahasiaan' =>
        'KJPP Sugianto Prasodjo dan Rekan akan menjaga kerahasiaan informasi yang diterima dan hanya '
        . 'menyampaikan informasi tersebut pada karyawan dan kuasanya yang berkepentingan dan tidak '
        . 'membocorkan informasi meliputi namun tidak terbatas pada informasi tentang dan menyangkut '
        . 'bisnis, perencanaan, data keuangan dan lain-lain kepada pihak ketiga termasuk afiliasi dan '
        . 'subsidiary.',
    'pendamping' =>
        'Pemberi tugas bersedia menyiapkan diri / memberikan kuasa pada orang yang bisa mewakili '
        . 'untuk menunjukkan lokasi dan mendampingi inspeksi lapangan. Apabila Pendamping Lapangan '
        . 'adalah orang lain selain Pemilik/Pemberi Tugas, verifikasi yang Penilai/Pelaksana Inspeksi '
        . 'lakukan melalui telepon dan/atau sms/wa kepada Pemilik/Pemberi Tugas untuk memastikan '
        . 'pendampingan tersebut dapat anggap sebagai pengganti surat kuasa dari Pemilik/Pemberi '
        . 'Tugas. Pendamping ini harus yang benar-benar mengetahui lokasi properti dan batas-batas '
        . 'properti, sehingga tidak akan salah dalam penentuan lokasi aset/properti yang dinilai. '
        . 'Dalam hal adanya kesalahan penunjukan lokasi baik yang ditunjukkan oleh Pemilik/Pemberi '
        . 'Tugas maupun Pendamping Lapangan, maka hal ini menjadi tanggung jawab sepenuhnya pemberi '
        . 'tugas.',
    'berita_acara' =>
        'Setiap akhir dari inspeksi lapangan yang dilakukan oleh Team Penilai dan penunjuk lapangan '
        . '(Counterpart) maka kedua pihak diwajibkan untuk menandatangani berita acara hasil '
        . 'pemeriksaan inspeksi lapangan.',

    // ------------------------------------------------------------------
    // 25. Biaya Jasa Penilaian
    // ------------------------------------------------------------------
    'biaya_intro' => 'Untuk melaksanakan pekerjaan penilaian ini, Biaya Profesional Jasa Penilaian adalah sebesar:',
    // Kalimat di bawah Total Fee — total SELALU sudah termasuk PPN; dipilih
    // sesuai apakah Transport & Akomodasi ikut ditagih (lihat
    // ProposalDocxBuilder::biayaCaptionLines).
    'biaya_ppn_included' => 'Biaya sudah termasuk PPN yang berlaku, Transportasi, Akomodasi.',
    // Varian bila Transport & Akomodasi ditanggung klien. Total biaya di
    // proposal SELALU sudah termasuk PPN (2026-09-15, keputusan user) —
    // pembedanya hanya TA ikut atau tidak, tanpa penjelasan tambahan.
    'biaya_transport_excluded' => 'Biaya sudah termasuk PPN yang berlaku, belum termasuk Transportasi dan Akomodasi.',
    'biaya_rincian_label' => 'Rincian Biaya :',
    'termin_label' => 'Termin Pembayaran :',
    // Termin dinamis (2026-09-19): persentase ikut skema/isian proposal.
    // :pct = angka persen, :pct_words = persen dalam huruf, :rp & :terbilang = nominal.
    'termin_item_first' => ':pct% (:pct_words persen) sebesar :rp (:terbilang), dibayarkan sebelum dilakukan inspeksi lapangan.',
    'termin_item_last'  => ':pct% (:pct_words persen) sebesar :rp (:terbilang), dibayarkan sebelum laporan final diserahkan.',
    'termin_item_mid'   => ':pct% (:pct_words persen) sebesar :rp (:terbilang), dibayarkan sebelum laporan final diserahkan.',
    'termin_1' => '50% (lima puluh persen) sebesar :rp (:terbilang), dibayarkan sebelum dilakukan inspeksi lapangan.',
    'termin_2' => '50% (lima puluh persen) sebesar :rp (:terbilang), dibayarkan sebelum laporan final diserahkan.',
    'rekening_label' => 'Rekening Bank :',
    'biaya_pembatalan' =>
        'Apabila terjadi pembatalan penugasan, pembayaran yang sudah dibayarkan kepada KJPP/Penilai '
        . 'tidak dapat dikembalikan.',

    // ------------------------------------------------------------------
    // 26. Pernyataan Pemberi Tugas + penutup
    // ------------------------------------------------------------------
    'pernyataan_pemberi_tugas' =>
        'Pemberi tugas menyatakan bahwa aset yang dinilai tidak sedang atau telah dinilai oleh Penilai '
        . 'Publik lainnya untuk maksud, tujuan, pengguna laporan, dan tanggal penilaian yang sama atau '
        . 'berdekatan (dalam jangka waktu tidak lebih dari dua bulan). (KEPI 5.8 C.4)',
    'penutup_spk' =>
        'Jika disetujui, proposal ini berlaku sebagai SPK efektif pada tanggal penandatanganan. Mohon '
        . 'tanda tangan di kolom persetujuan dan paraf pada setiap halaman. Jika dokumen memuat '
        . 'barcode/QR KJPP SPR, keabsahan diverifikasi melalui pemindaian dan sah bila data yang '
        . 'tampil identik. Jika tanpa barcode/QR, keabsahan ditentukan oleh tanda tangan & paraf serta '
        . 'kesesuaian identitas dokumen. Perubahan/penggantian/penambahan halaman tanpa persetujuan '
        . 'tertulis KJPP Sugianto Prasodjo & Rekan membatalkan keabsahan. Masa berlaku penawaran 1 '
        . '(satu) bulan kalender sejak tanggal proposal ini (setelahnya ketentuan/biaya dapat ditinjau '
        . 'kembali).',
];
