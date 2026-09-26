<?php

/**
 * =============================================================================
 * TEKS BAKU PROPOSAL JASA KONSULTASI (SPI 350)
 *
 * Disalin apa adanya dari tiga master proposal kantor (2026-09-26):
 *   005_MASTER PROPOSAL_KAJIAN KEWAJARAN RAB.docx
 *   000_MASTER_PROPOSAL FS.docx
 *   006_MASTER PROPOSAL_PENGAWASAN_13.12.2025.docx
 *
 * Markup mengikuti kesepakatan aplikasi:
 *   **tebal**            *miring*            ***tebal miring***
 *   "a. " / "1. " / "- " di awal baris menjadi daftar rapi
 *   :klien, :pengguna_laporan, :objek_pekerjaan, ... = isian otomatis
 *
 * Nilai di sini hanya BAWAAN. Administrator & General Admin menyuntingnya
 * lewat Pengaturan Sistem > Teks Baku Proposal tanpa mengubah kode.
 * =============================================================================
 */
return [

    'rab' => [
        'perihal'     => 'Proposal Pekerjaan Konsultasi Kajian Kewajaran Rencana Anggaran Biaya (RAB) Pembangunan #####',
        'pembuka'     => 'Menindaklanjuti informasi dan komunikasi dengan pihak **:klien** yang disampaikan melalui email pada tanggal **## ### ####**, maka bersama ini kami **Kantor Jasa Penilai Publik(KJPP) SUGIANTO PRASODJO DAN REKAN,** menyampaikan proposal Pekerjaan Konsultasi dalam rangka melakukan Kajian Kewajaran Rencana Anggaran Biaya (RAB) Pembangunan #### yang diprakarsai oleh **:klien.**',
        'biaya_intro' => 'Untuk melaksanakan pekerjaan penilaian ini, kami mengajukan Biaya Profesional Jasa Konsultasi Analisis Kewajaran adalah sebesar :',
        'termin_first' => ':pct% (:pct_words persen) sebesar **:rp (:terbilang),** dibayarkan sebelum dilakukan inspeksi lapangan.',
        'termin_mid' => ':pct% (:pct_words persen) sebesar **:rp (:terbilang),** dibayarkan setelah draft laporan diserahkan.',
        'termin_last' => ':pct% (:pct_words persen) sebesar **:rp (:terbilang),** dibayarkan sebelum laporan final diserahkan.',
        'termin_tahap' => false,
        'kalimat_surat_tugas' => 'melakukan inspeksi lokasi dan analisis kewajaran Rencana Anggaran Biaya (RAB) atas nama :klien.',
        'judul_biaya' => 'Biaya Jasa Konsultasi Analisis Kewajaran',
        'judul_tor'   => '',
        'bab' => [
            'pendahuluan' => [
                'judul' => "Pendahuluan",
                'teks'  => "**:klien** adalah salah satu perusahaan swasta yang berkantor di #### yang di mana perusahaan ini merupakan perusahaan yang bergerak di bidang ####.",
            ],
            'latar_belakang_penugasan' => [
                'judul' => "Latar Belakang Penugasan",
                'teks'  => "Maksud dan tujuan penugasan ini adalah untuk menyajikan informasi tentang Kewajaran Rencana Anggaran Biaya (RAB) dalam rencana pembangunan **-----------------** yang telah dikembangkan oleh **:klien** dan digunakan sebagai salah satu informasi pendukung dalam rangka pembiayaan dengan pihak **:klien**.",
            ],
            'pemberi_tugas_dan_pengguna_laporan' => [
                'judul' => "Pemberi Tugas dan Pengguna Laporan",
                'teks'  => "Pemberi tugas dan pengguna laporan adalah **:klien.**",
            ],
            'objek_pekerjaan' => [
                'judul' => "Objek Pekerjaan",
                'teks'  => "Yang menjadi objek dalam pekerjaan ini adalah Rencana Anggaran Biaya (RAB) terhadap rencana pembangunan **####** yang dikembangkan oleh **:klien** yang berlokasi di ####.",
            ],
            'bentuk_kepemilikan' => [
                'judul' => "Bentuk Kepemilikan",
                'teks'  => "Dalam hal ini kami berasumsi, kepemilikan lahan yang telah dikembangkan yang menjadi objek dalam pekerjaan ini merupakan milik **:klien** dalam status kepemilikan *Free and Clear/Clean and Clear (CnC).*",
            ],
            'maksud_dan_tujuan_penugasan' => [
                'judul' => "Maksud dan Tujuan Penugasan",
                'teks'  => "Tujuan dari pekerjaan ini sebagaimana diinformasikan kepada kami adalah untuk melakukan kajian dan analisis kewajaran atas Rencana Anggaran Biaya (RAB) yang telah disusun oleh pihak pemrakarsa. Hasil kajian tersebut akan digunakan oleh pemberi tugas sebagai salah satu bahan pertimbangan dalam rangka pemberian fasilitas kredit konstruksi. Oleh karena itu, laporan hasil analisis kewajaran ini disusun khusus untuk kepentingan dimaksud dan tidak diperuntukkan bagi penggunaan lainnya tanpa persetujuan tertulis dari pihak terkait.",
            ],
            'lingkup_pekerjaan' => [
                'judul' => "Lingkup Pekerjaan",
                'teks'  => "Lingkup pekerjaan dalam penyusunan Jasa Konsultasi adalah sebagai berikut:\n"
                          . "a. Pengumpulan data kualitatif/kuantitatif, primer dan skunder\n"
                          . "b. Pelaksanaan inspeksi dan verifikasi objek kajian.\n"
                          . "c. Analisis kewajaran atas Rencana Anggaran Biaya (RAB)\n"
                          . "d. Penyusunan dan pelaporan hasil kajian kewajaran RAB\n",
            ],
            'asumsi_batasan' => [
                'judul' => "Asumsi & Batasan",
                'teks'  => "a. Kajian kewajaran Rencana Anggaran Biaya (RAB) dilakukan berdasarkan kondisi pasar, harga material, upah tenaga kerja, serta biaya konstruksi yang berlaku pada tanggal kajian. Perubahan kondisi ekonomi, inflasi, maupun fluktuasi harga setelah tanggal kajian dapat mempengaruhi hasil analisis ini.\n"
                          . "b. Analisis dan perhitungan dilakukan berdasarkan data dan dokumen yang diperoleh dari pemberi tugas, pemrakarsa proyek, maupun sumber lain yang dianggap dapat dipercaya, baik berupa data kualitatif maupun kuantitatif, primer maupun sekunder.\n"
                          . "c. Kajian ini tidak dimaksudkan sebagai audit teknis, audit hukum, maupun studi kelayakan proyek secara menyeluruh, melainkan terbatas pada analisis kewajaran atas Rencana Anggaran Biaya (RAB) yang disampaikan kepada kami.\n"
                          . "d. Kami mengasumsikan bahwa seluruh data, gambar kerja, spesifikasi teknis, volume pekerjaan, serta dokumen pendukung lainnya yang diberikan kepada kami adalah benar, lengkap, dan dapat dipertanggungjawabkan.\n"
                          . "e. Dalam konteks legalitas, kami mengasumsikan bahwa proyek yang dikaji telah atau akan memenuhi ketentuan perizinan dan persyaratan hukum yang berlaku sesuai peraturan perundang-undangan yang berlaku.\n"
                          . "f. Hasil kajian kewajaran ini disusun untuk digunakan oleh pemberi tugas dalam rangka pertimbangan pemberian fasilitas kredit konstruksi dan tidak diperuntukkan untuk tujuan lainnya tanpa persetujuan tertulis dari pihak terkait.\n",
            ],
            'penjelasan_status_penilai_konsultan' => [
                'judul' => "Penjelasan Status Penilai (Konsultan)",
                'teks'  => "Penilai Publik yang bertanggung jawab sekaligus yang bertanda tangan di dalam Laporan Penilaian ini adalah **Arief Rachman Setiady, S.M., M.M, MAPPI (Cert.)** merupakan Penilai Publik Properti dengan Nomor Izin Penilai Publik **No. P.1.25.00690** berdasarkan Surat Keputusan Menteri Keuangan Nomor **185/MK/SJ/2025** Tanggal **23 April 2025.**\n"
                          . "\n"
                          . "Penilai bertindak atas nama Kantor Jasa Penilai Publik SUGIANTO PRASODJO DAN REKAN memiliki Izin Usaha resmi dari Kementerian Keuangan Republik Indonesia No. 2.15.0131. KJPP Sugianto Prasodjo dan Rekan adalah perusahaan penilai independen yang terdaftar di Masyarakat Profesi Penilai Indonesia (MAPPI) dan terdaftar di Otoritas Jasa Keuangan/OJK (d/h Bapepam-LK) berdasarkan Surat Tanda Terdaftar Profesi Penunjang Pasar Modal No. S-859/PM.223/2015 tanggal 17 November 2015.\n"
                          . "\n"
                          . "Dalam pekerjaan ini penilai bertindak sebagai konsultan, dan dalam menjalankan pekerjaan ini penilai bertindak sesuai dengan yang diatur dalam **SPI Edisi VII 2018** dan **Edisi Revisi 2020(SPI 350 – Jasa Konsultasi)** dan **PMK No. 228** terkait dengan pemberian jasa lainnya.\n",
            ],
            'jenis_mata_uang' => [
                'judul' => "Jenis Mata Uang",
                'teks'  => "Jenis Mata Uang yang akan digunakan dalam laporan penilaian adalah Rupiah (Rp).",
            ],
            'benturan_kepentingan' => [
                'judul' => "Benturan Kepentingan",
                'teks'  => "Kami sebagai penilai yang bertindak sebagai konsultan menyatakan bahwa status kami adalah sebagai penilai independen (konsultan). Kami menyatakan bahwa tidak ada keterlibatan material dan benturan kepentingan baik yang aktual maupun bersifat potensial terhadap obyek kajian.",
            ],
            'kondisi_pembatas' => [
                'judul' => "Kondisi Pembatas",
                'teks'  => "Berdasarkan identifikasi awal, terdapat kondisi pembatas dalam pelaksanaan konsultan yaitu sebatas pada tujuan dan ruang lingkup.",
            ],
            'data_data_yang_diperlukan_antara_lain' => [
                'judul' => "Data-data yang diperlukan antara lain :",
                'teks'  => "Secara umum data terbagi menjadi :\n"
                          . "a. Rencana Anggaran Biaya (RAB) beserta *breakdown* pekerjaan.\n"
                          . "b. Gambar kerja dan spesifikasi teknis proyek.\n"
                          . "c. Bill of Quantity (BoQ) / volume pekerjaan.\n"
                          . "d. Price list / penawaran harga material dan pekerjaan dari kontraktor atau supplier (jika tersedia).\n"
                          . "e. Jadwal pelaksanaan pekerjaan (time schedule proyek).\n"
                          . "f. Dokumen legalitas dan perizinan proyek yang relevan.\n"
                          . "g. Berita Acara Inspeksi atau hasil kunjungan lapangan.\n"
                          . "h. Surat Pernyataan Kebenaran Data dari pihak pemrakarsa/pemberi tugas.\n"
                          . "i. Dokumen pendukung lainnya yang dianggap perlu dalam proses analisis kewajaran.\n",
            ],
            'prosedur_pelaksanaan_penugasan' => [
                'judul' => "Prosedur Pelaksanaan Penugasan",
                'teks'  => "Penugasan penilaian ini akan dilakukan menurut prosedur dengan tahapan-tahapan sebagai berikut :\n"
                          . "1. Pengumpulan data dan dokumen awal yang berkaitan dengan proyek dan RAB yang akan dikaji.\n"
                          . "2. Pelaksanaan pemeriksaan dan inspeksi lapangan untuk memperoleh informasi mengenai kondisi aktual, spesifikasi teknis, serta kesesuaian objek kajian dengan dokumen yang disampaikan.\n"
                          . "3. Melakukan analisis dan evaluasi kewajaran Rencana Anggaran Biaya (RAB) berdasarkan data pasar, spesifikasi teknis, volume pekerjaan, serta data pendukung lainnya yang relevan.\n"
                          . "4. Penyusunan laporan hasil kajian kewajaran RAB beserta kesimpulan dan rekomendasi sesuai tujuan penugasan.\n",
            ],
            'team_pelaksana' => [
                'judul' => "Team Pelaksana",
                'teks'  => "Dalam pelaksanaan pekerjaan ini, pihak KJPP membuat struktur pelaksana pekerjaan sebagai berikut:\n"
                          . "- Penanggung Jawab : 1 Orang\n"
                          . "- Reviewer : 1 Orang\n"
                          . "- Analisyst : 1 Orang\n"
                          . "- *Supporting* : 1 Orang\n",
            ],
            'pelaporan' => [
                'judul' => "Pelaporan",
                'teks'  => "**Laporan Final akan diserahkan** dalam waktu 14 (empat belas) hari kerja setelah inspeksi lapangan dan wawancara dengan pihak manajemen **:klien.**",
            ],
            'ruang_lingkup_laporan' => [
                'judul' => "Ruang Lingkup Laporan",
                'teks'  => "1. Pendahuluan\n"
                          . "2. Pendekatan dan Metodologi Kajian\n"
                          . "3. Gambaran Umum Proyek\n"
                          . "4. Data & Dokumen Pendukung\n"
                          . "5. Analisa Teknis & Kewajaran Biaya Konstruksi\n"
                          . "6. Analisa Kewajaran RAB\n"
                          . "7. Kesimpulan & Opini Kewajaran\n"
                          . "8. Lampiran\n",
            ],
            'pembatalan_penugasan' => [
                'judul' => "Pembatalan Penugasan",
                'teks'  => "Pembatalan penugasan secara sepihak oleh Pemberi Tugas tidak membebaskakren Pemberi Tugas dari kewajiban-kewajiban terhadap Penilai/Konsultan. Tujuan penugasan tidak memiliki hubungan ataupun kepentingan dengan pekerjaan yang dilakukan oleh Penilai/Konsultan dan tidak dapat dijadikan alasan oleh Pemberi Tugas untuk pembatalan penugasan.",
            ],
            'kerahasiaan_informasi' => [
                'judul' => "Kerahasiaan Informasi",
                'teks'  => "KJPP Sugianto Prasodjo dan Rekan akan menjaga kerahasiaan informasi yang diterima dan hanya menyampaikan informasi tersebut pada karyawan dan kuasanya yang berkepentingan dan tidak membocorkan informasi meliputi namun tidak terbatas pada informasi tentang dan menyangkut bisnis, perencanaan, data keuangan dan lain-lain kepada pihak ketiga termasuk afiliasi dan subsidiari.\n"
                          . "\n"
                          . "Proposal penawaran biaya jasa konsultasi analisis kajian harga jual yang kami sampaikan ini juga sekaligus sebagai Surat Perjanjian Kerja (SPK) dan sebagai tanda persetujuan dari pihak pemberi tugas, kami mohon agar pemberi tugas dapat menandatangani di tempat tersedia di bawah ini, atas perhatian dan kerjasamanya kami ucapkan banyak terima kasih.\n",
            ],
        ],
        'tor' => [
        ],
        'lampiran2' => [
        ],
    ],

    'fs' => [
        'perihal'     => 'Proposal Penawaran Jasa Penyusunan Laporan Studi Kelayakan *(Feasibility Study)* Proyek Pembangunan Pabrik Pengolahan Tembakau :klien',
        'pembuka'     => 'Menindaklanjuti permintaan melalui telepon untuk penyusunan Studi Kelayakan *(Feasibility Study)* proyek pembangunan Pabrik Pengolahan Tembakau yang diprakarsai oleh **:klien**, bersama ini kami dari KJPP Sugianto Prasodjo dan Rekan menyampaikan proposal penawaran biaya jasa penyusunan Studi Kelayakan dilengkapi dengan Kerangka Acuan Kerja (KAK), sebagai berikut:',
        'biaya_intro' => 'Untuk melaksanakan pekerjaan studi ini, kami mengajukan biaya profesional jasa studi kelayakan adalah sebesar:',
        'termin_first' => 'Tahap :tahap : :pct% dari biaya jasa sebesar **:rp (:terbilang),** dibayarkan pada saat penandatanganan Surat Perjanjian Kerja (SPK)',
        'termin_mid' => 'Tahap :tahap : :pct% dari biaya jasa sebesar **:rp (:terbilang),** dibayarkan setelah draft laporan diserahkan.',
        'termin_last' => 'Tahap :tahap : :pct% dari biaya jasa sebesar **:rp (:terbilang),** dibayarkan sebelum final report diserahkan.',
        'termin_tahap' => true,
        'kalimat_surat_tugas' => 'melakukan Jasa Penyusunan Laporan Studi Kelayakan atas nama :klien.',
        'judul_biaya' => 'Biaya Jasa Studi Kelayakan',
        'judul_tor'   => 'Penyusunan Studi Kelayakan',
        'bab' => [
            'ringkasan_proposal' => [
                'judul' => "Ringkasan Proposal",
                'teks'  => "Proposal ini bertujuan memberikan gambaran menyeluruh mengenai ruang lingkup pekerjaan, pendekatan analisis, jadwal pelaksanaan, tim pelaksana, serta estimasi biaya jasa untuk penyusunan Studi Kelayakan proyek pembangunan Pabrik Pengolahan Tembakau yang diprakarsai oleh **:klien** yang berlokasi di JI. Raya Mantup Lamongan KM 16 RT 05 RW 01 Desa Dumpiagung Kecamatan Kembangbahu Kabupaten Lamongan, Provinsi Jawa Timur.",
            ],
            'latar_belakang_dan_tujuan_pekerjaan' => [
                'judul' => "Latar Belakang dan Tujuan Pekerjaan",
                'teks'  => "Latar belakang penyusunan studi ini adalah perlunya kajian komprehensif untuk menilai kelayakan rencana proyek yang akan dilaksanakan oleh **:klien**. Kajian tersebut mencakup analisis pasar, teknis, operasional, hukum, lingkungan, serta aspek finansial guna memastikan bahwa proyek dapat dijalankan secara efektif, efisien, dan memberikan manfaat yang optimal. Studi kelayakan ini disusun sebagai dasar pertimbangan manajemen dalam mengambil keputusan investasi dan arahan pengembangan proyek ke depan.",
            ],
            'penjelasan_status_penilai_konsultan' => [
                'judul' => "Penjelasan Status Penilai (Konsultan)",
                'teks'  => "Penilai Publik dalam pekerjaan sebagai konsultan yang bertanggung jawab sekaligus yang bertanda tangan di dalam Laporan Studi Kelayakan ini adalah **Nama Penilai Publik, MAPPI (Cert.),** merupakan Penilai Publik Properti dengan lzin Penilai Publik **No. P.1.15.00425** berdasarkan Surat Keputusan Menteri Keuangan Republik Indonesia Nomor 328/KM.1/2015 tanggal **21 April 2015**.\n"
                          . "\n"
                          . "Penilai bertindak atas nama **Kantor Jasa Penilai Publik SUGIANTO PRASODJO DAN REKAN** memiliki **lzin Usaha resmi dari Kementerian Keuangan Republik Indonesia No. 2.15.0131** berdasarkan **Kepmenkeu No. 722/KM.1/2015** tanggal **09 September 2015** dari Menteri Keuangan Republik Indonesia**.** KJPP Sugianto Prasodjo dan Rekan adalah perusahaan penilai independen yang terdaftar di Masyarakat Profesi Penilai Indonesia (MAPPI)\n"
                          . "\n"
                          . "dan terdaftar di Otoritas Jasa Keuangan/OJK (d/h Bapepam-LK) berdasarkan **Surat Tanda Terdaftar Profesi Penunjang Pasar Modal No. S-859/PM.223/2015** tanggal **17 November 2015**.\n",
            ],
            'pemberi_tugas_dan_pengguna_laporan' => [
                'judul' => "Pemberi Tugas dan Pengguna Laporan",
                'teks'  => "Pemberi Tugas dalam Penyusunan Studi Kelayakan ini adalah **:klien**, sedangkan Pengguna Laporan dalam pekerjaan ini adalah **:klien dan PT Bank Rakyat Indonesia, (Persero) Tbk**.",
            ],
            'ruang_lingkup_pekerjaan' => [
                'judul' => "Ruang Lingkup Pekerjaan",
                'teks'  => "Pekerjaan yang dimaksud adalah penyusunan **Studi Kelayakan (Feasibility Study)** finansial atas rencana pembagunan proyek yang dilakukan oleh **:klien**. Tujuan dari kajian ini adalah untuk memberikan analisis dan opini profesional mengenai berbagai aspek yang mempengaruhi kelayakan proyek, melalui kegiatan sebagai berikut:\n"
                          . "a. **Menganalisis aspek pasar dan komersial**, mencakup potensi permintaan, proyeksi kebutuhan, kondisi persaingan usaha, serta tren dan prospek industri pengguna utama.\n"
                          . "b. **Menganalisis aspek teknis dan operasional**, termasuk tinjauan terhadap teknologi yang diusulkan, kapasitas produksi, ketersediaan infrastruktur, dan efisiensi rantai pasok berdasarkan informasi yang disediakan.\n"
                          . "c. **Melakukan analisis terhadap aspek keuangan dan ekonomi**, melalui penyusunan model keuangan untuk mengevaluasi indikator kelayakan seperti profitabilitas, arus kas, ROI, sensitivitas, dan nilai proyek.\n"
                          . "d. **Melakukan analisis aspek organisasi dan sumber daya manusia**, termasuk struktur manajemen, sistem pengelolaan operasional, serta kesiapan SDM dalam mendukung pelaksanaan dan keberlanjutan proyek.\n"
                          . "e. **Mengidentifikasi potensi risiko** yang mungkin dapat memengaruhi pelaksanaan dan keberlanjutan proyek, serta memberikan pandangan umum mengenai langkah mitigasi yang dapat dipertimbangkan.\n"
                          . "f. **Menyusun kesimpulan dan rekomendasi profesional** mengenai tingkat kelayakan proyek, yang disajikan dalam bentuk opini *“Feasible”* atau *“Not Feasible”*, berdasarkan hasil analisis dan asumsi yang digunakan.\n"
                          . "g. **Menyusun ringkasan kelayakan finansial untuk kebutuhan pihak pendanaan**, sebagai dasar komunikasi dengan lembaga pembiayaan atau calon investor **(tanpa bersifat rekomendatif)**.\n"
                          . "Lingkup pekerjaan ini dilaksanakan dengan metodologi yang terstruktur, meliputi studi pustaka, pengumpulan data primer dan sekunder, analisis mendalam melalui pemodelan keuangan, validasi aspek teknis dan legal, serta penyusunan hasil kajian dalam bentuk laporan awal, draf laporan akhir, presentasi, dan laporan final.\n",
            ],
            'peraturan_standar_dan_konsep' => [
                'judul' => "Peraturan, Standar dan Konsep",
                'teks'  => "Dalam Menyusun laporan Studi Kelayakan, konsultan mengacu kepada peraturan dan standar antara lain:\n"
                          . "a. Kode Etik (KEPI) & Standar Penilaian Indonesia (SPI) **Edisi VII Tahun 2018** dan **Edisi VII Revisi Tahun 2020 SPI 350** tentang jasa konsultasi lainnya.\n"
                          . "b. **Peraturan Menteri Keuangan Republik Indonesia Nomor 101/PMK.01/2014 tentang Penilai Publik**, sebagaimana telah diubah terakhir dengan **Peraturan Menteri Keuangan Republik Indonesia Nomor 228/PMK.01/2019**, serta ketentuan lain yang mengatur Penilai Publik dan Kantor Jasa Penilai Publik.\n"
                          . "c. Standar Akuntansi Keuangan (SAK) yang berlaku efektif di Indonesia, sebagai dasar penyusunan dan interpretasi proyeksi keuangan dalam analisis kelayakan finansial.\n"
                          . "d. Beberapa konsep teori akademik yang umum digunakan dalam penyusunan studi kelayakan seperti: *investment decision theory, project life cycle theory, supply demand theory, proter’s five forces, time value of money, discounted cash flow, capital structure theory, externality theory, sustainable development* dan lainnya.\n"
                          . "e. **Peraturan perundang-undangan di bidang perindustrian dan industri hasil tembakau**, termasuk ketentuan mengenai perizinan berusaha berbasis risiko, perizinan dan kegiatan industri, cukai hasil tembakau, ketentuan mengenai pabrik hasil tembakau, serta ketentuan teknis lainnya yang diterbitkan oleh kementerian/lembaga yang berwenang.\n"
                          . "f. **Peraturan di bidang lingkungan hidup**, termasuk ketentuan mengenai persetujuan lingkungan, AMDAL atau UKL-UPL sesuai skala dan karakteristik kegiatan, pengelolaan limbah, emisi, air limbah, serta ketentuan lingkungan lainnya yang relevan dengan pembangunan dan operasional pabrik.\n"
                          . "g. **Peraturan di bidang tata ruang, pertanahan, bangunan gedung dan infrastruktur**, termasuk kesesuaian kegiatan pemanfaatan ruang, persetujuan bangunan gedung, sertifikat laik fungsi, ketentuan kawasan industri apabila relevan, serta ketentuan pemerintah daerah yang berlaku pada lokasi proyek.\n"
                          . "h. **Peraturan di bidang ketenagakerjaan serta keselamatan dan kesehatan kerja (K3)** yang relevan dengan tahap pembangunan maupun tahap operasional pabrik.\n"
                          . "i. Peraturan, standar teknis, dan pedoman lainnya yang diterbitkan oleh **Kementerian Perindustrian, Kementerian Keuangan/Direktorat Jenderal Bea dan Cukai, Kementerian Lingkungan Hidup, Kementerian Ketenagakerjaan, Kementerian ATR/BPN, pemerintah daerah**, dan instansi berwenang lainnya, sepanjang relevan dengan pembangunan dan kegiatan operasional pabrik pengolahan tembakau.\n",
            ],
            'struktur_laporan_studi' => [
                'judul' => "Struktur Laporan Studi",
                'teks'  => "a. Latar Belakang dan Gambaran Umum Proyek\n"
                          . "b. Aspek Legalitas dan Perizinan\n"
                          . "c. Aspek Teknis\n"
                          . "d. Aspek Pasar dan Pemasaran\n"
                          . "e. Aspek Teknis dan Operasional\n"
                          . "f. Aspek Manajemen & Sumber Daya Manusia\n"
                          . "g. Analisis Aspek Sosial, Lingkungan, dan Keberlanjutan (ESG)\n"
                          . "h. Aspek Keuangan dan Kelayakan Investasi\n"
                          . "i. Analisis Risiko dan Sensitivitas\n"
                          . "j. Kesimpulan & Rekomendasi\n",
            ],
            'asumsi_batasan' => [
                'judul' => "Asumsi & Batasan",
                'teks'  => "a. Analisa dan Perhitungan dilakukan terhadap data yang diasumsikan benar disajikan oleh manajemen\n"
                          . "b. Sumber informasi lainnya yang dapat diandalkan diambil dari sumber terpercaya yang tidak memerlukan konfirmasi konsultan.\n"
                          . "c. Analisa dan Perhitungan dilakukan konsultan terbatas pada rekomendasi yang tidak mutlak dilaksanakan oleh pemberi tugas\n"
                          . "d. Kami tidak melakukan pekerjaan due diligence atas dua perusahaan dimaksud, sehingga kami melakukan review terbatas pada laporan-laporan pihak lain.\n",
            ],
            'tim_pelaksana' => [
                'judul' => "Tim Pelaksana",
                'teks'  => "Dalam melakukan Pekerjaan Studi Kelayakan proyek Pembangunan Pabrik Pengolahan Tembakau yang diprakarsai oleh **:klien**, tenaga ahli yang terlibat secara langsung dan tidak langsung adalah sebagai berikut:\n"
                          . "\n"
                          . "Tenaga Ahli\n",
            ],
            'jenis_mata_uang' => [
                'judul' => "Jenis Mata Uang",
                'teks'  => "Jenis Mata Uang yang akan digunakan dalam laporan studi kelayakan ini adalah Rupiah (Rp).",
            ],
            'benturan_kepentingan' => [
                'judul' => "Benturan Kepentingan",
                'teks'  => "Kami sebagai penilai dalam hal ini bertindak sebagai konsultan menyatakan bahwa status kami adalah sebagai penilai independen. Kami menyatakan bahwa tidak ada keterlibatan material dan benturan kepentingan baik yang aktual maupun bersifat potensial terhadap objek studi.",
            ],
            'kondisi_pembatas_konsultan' => [
                'judul' => "Kondisi Pembatas Konsultan",
                'teks'  => "Berdasarkan identifikasi awal, terdapat kondisi pembatas dalam pelaksanaan konsultan yaitu sebatas pada tujuan dan ruang lingkup.",
            ],
            'data_data_yang_diperlukan_antara_lain' => [
                'judul' => "Data-data yang diperlukan antara lain :",
                'teks'  => "Kebutuhan data secara detail dapat dilihat pada lampiran-2, secara umum data terbagi menjadi:\n"
                          . "a. Pembahasan\n"
                          . "1. Aspek Legal dan Perizinan\n"
                          . "2. Aspek Pasar dan Pemasaran\n"
                          . "3. Aspek Teknis dan Operasional, termasuk data progres pembangunan, realisasi investasi, spesifikasi teknis, kapasitas produksi, mesin dan peralatan, utilitas, jadwal penyelesaian proyek, serta estimasi biaya penyelesaian proyek (*cost to complete*).\n"
                          . "4. Aspek Manajemen dan Sumber Daya Manusia\n"
                          . "5. Aspek Sosial, Lingkungan, dan Keberlanjutan (ESG)\n"
                          . "6. Aspek Keuangan, termasuk data historis apabila tersedia, realisasi investasi, sumber dan struktur pendanaan, kebutuhan tambahan investasi, modal kerja, asumsi pendapatan dan biaya, serta proyeksi keuangan.\n"
                          . "7. Data dan Informasi Pendukung Lainnya yang relevan dengan karakteristik proyek dan diperlukan dalam pelaksanaan analisis Studi Kelayakan.\n"
                          . "b. Administrasi\n"
                          . "8. Berita Acara Inspeksi/Survei Lapangan.\n"
                          . "9. Surat Pernyataan Kebenaran Data dan Informasi.\n"
                          . "10. Nama dan kontak (*contact person*) pihak yang berwenang untuk memberikan data, melakukan klarifikasi, serta berdiskusi dengan Konsultan.\n"
                          . "11. Dokumen, data, klarifikasi, dan informasi tambahan lainnya yang diperlukan selama proses pelaksanaan Studi Kelayakan.\n",
            ],
            'prosedur_pelaksanaan_penugasan' => [
                'judul' => "Prosedur Pelaksanaan Penugasan",
                'teks'  => "Penugasan studi ini akan dilakukan menurut prosedur dengan tahapan-tahapan sebagai berikut :\n"
                          . "a. Persiapan dan pengumpulan data awal.\n"
                          . "b. Pemeriksaan dan penelitian lapangan, untuk memperoleh data akurat tentang spesifikasi dan kondisi sebenarnya dari objek penugasan, sebagai dasar menyusun berbagai aspek yang akan analisa.\n"
                          . "c. Melakukan analisa dan perhitungan rencana proyek.\n"
                          . "d. Penyusunan hasil Analisa.\n"
                          . "e. Penyusunan laporan final studi kelayakan.\n",
            ],
            'pelaporan' => [
                'judul' => "Pelaporan",
                'teks'  => "Laporan final akan diserahkan dalam jangka waktu 21 (duapuluh satu) hari kerja, dengan timeline pekerjaan sebagaimana tercantum pada Lampiran 1 Kerangka Acuan Kerja (KAK)/*Term of Reference (ToR)* bagian IV.",
            ],
            'pembatalan_penugasan' => [
                'judul' => "Pembatalan Penugasan",
                'teks'  => "Pembatalan penugasan secara sepihak oleh Pemberi Tugas tidak membebaskan Pemberi Tugas dari kewajiban-kewajiban terhadap Penilai. Tujuan penugasan tidak memiliki hubungan ataupun kepentingan dengan pekerjaan yang dilakukan oleh Penilai dan tidak dapat dijadikan alasan oleh Pemberi Tugas untuk pembatalan penugasan.",
            ],
            'kerahasiaan_informasi' => [
                'judul' => "Kerahasiaan Informasi",
                'teks'  => "KJPP Sugianto Prasodjo dan Rekan akan menjaga kerahasiaan informasi yang diterima dan hanya menyampaikan informasi tersebut pada karyawan dan kuasanya yang berkepentingan dan tidak membocorkan informasi meliputi namun tidak terbatas pada informasi tentang dan menyangkut bisnis, perencanaan, data keuangan dan lain-lain kepada pihak ketiga termasuk afiliasi dan subsidiari.\n"
                          . "\n"
                          . "Jika disetujui, proposal ini berlaku sebagai SPK efektif pada tanggal penandatanganan. Mohon tanda tangan di kolom persetujuan dan paraf pada setiap halaman. Jika dokumen memuat barcode/QR KJPP SPR, keabsahan diverifikasi melalui pemindaian dan sah bila data yang tampil identik. Jika tanpa barcode/QR, keabsahan ditentukan oleh tanda tangan & paraf serta kesesuaian identitas dokumen. Perubahan/penggantian/penambahan halaman tanpa persetujuan tertulis KJPP Sugianto Prasodjo & Rekan membatalkan keabsahan.\n",
            ],
        ],
        'tor' => [
            'tor_definisi' => [
                'judul' => "DEFINISI",
                'teks'  => "a. **Pemberi Tugas**\n"
                          . "Yang dimaksud dengan Pemberi Tugas adalah **:klien**, berkedudukan di di Lamongan-Jawa Timur, yang memberikan penugasan penyusunan Studi Kelayakan Proyek Pembangunan Pabrik Pengolahan Tembakau.\n"
                          . "a. **Konsultan**\n"
                          . "Yang dimaksud dengan Konsultan adalah KJPP Sugianto Prasodjo dan Rekan, berkedudukan di Jakarta, dengan alamat 18th Office Park, Lantai 3E, Jl. TB Simatupang Kavling 18, Jakarta Selatan, yang ditunjuk untuk melaksanakan penyusunan laporan studi kelayakan ini.\n"
                          . "a. **Proyek**\n"
                          . "Proyek yang dimaksud adalah penyusunan Studi Kelayakan (*Feasibility Study*) atas Proyek Pembangunan Pabrik Pengolahan Tembakau yang sedang dilaksanakan oleh :klien, berlokasi di Jl. Raya Mantup–Lamongan KM 16, RT 05/RW 01, Desa Dumpiagung, Kecamatan Kembangbahu, Kabupaten Lamongan, yang mencakup kajian terhadap aspek legal dan perizinan, aspek pasar dan pemasaran, aspek teknis dan operasional termasuk evaluasi progres pembangunan dan kebutuhan penyelesaian proyek (*cost to complete*), aspek manajemen dan sumber daya manusia, aspek sosial, lingkungan dan keberlanjutan (ESG), aspek keuangan dan kelayakan investasi, serta analisis risiko dan sensitivitas, sebagai dasar dalam memberikan kesimpulan dan rekomendasi atas kelayakan Proyek.\n"
                          . "a. **Pemberi Tugan & Pengguna Laporan**\n"
                          . "Pemberi Tugas adalah :klien, sedangkan pengguna laporan adalah Pengguna Laporan adalah :klien dan PT BANK RAKYAT INDONESIA (PERSERO) Tbk, yang akan menggunakan hasil Studi Kelayakan ini sebagai salah satu bahan pertimbangan dalam pengambilan keputusan terkait kelayakan proyek, investasi, serta pembiayaan pembangunan dan penyelesaian proyek.\n"
                          . "a. **Asumsi & Batasan**\n"
                          . "1. Analisa dan Perhitungan dilakukan terhadap data yang diasumsikan benar disajikan oleh manajemen\n"
                          . "2. Sumber informasi lainnya yang dapat diandalkan diambil dari sumber terpercaya yang tidak memerlukan konfirmasi konsultan\n"
                          . "3. Analisa dan Perhitungan dilakukan konsultan terbatas pada rekomendasi yang tidak mutlak dilaksanakan oleh pemberi tugas\n"
                          . "4. Kami tidak melakukan pekerjaan *due diligence* atas dua perusahaan dimaksud, sehingga kami melakukan review terbatas pada laporan-laporan pihak lain.\n"
                          . "b. **Penjelasan Status Penilai**\n"
                          . "Penilai dalam hal ini bertindak sebagai Konsultan dan yang bertanggung jawab sekaligus menandatangani Laporan Studi Kelayakan ini adalah **Ir. Heru Setyabudi, ASEAN Eng., M.Ec.Dev., MAPPI(Cert.)**, yang memiliki izin praktik sebagai **Profesional Publik** di **bidang Properti** berdasarkan **Surat Keputusan Menteri Keuangan Republik Indonesia Nomor** 328/KM.1/2015 **tanggal 21 April 2015**, dengan **Nomor Izin P-1.15.00425**.\n"
                          . "a. **Peraturan, Standar dan Konsep**\n"
                          . "Peraturan dan standar yang digunakan dalam penyusunan studi kelayakan ini adalah:\n"
                          . "1. Kode Etik (KEPI) & Standar Penilaian Indonesia (SPI) **Edisi VII Tahun 2018** dan **Edisi VII Revisi Tahun 2020 SPI 350** tentang jasa konsultasi lainnya.\n"
                          . "2. **Peraturan Menteri Keuangan Republik Indonesia Nomor 101/PMK.01/2014 tentang Penilai Publik**, sebagaimana telah diubah terakhir dengan **Peraturan Menteri Keuangan Republik Indonesia Nomor 228/PMK.01/2019**, serta ketentuan lain yang mengatur Penilai Publik dan Kantor Jasa Penilai Publik.\n"
                          . "3. Standar Akuntansi Keuangan (SAK) yang berlaku efektif di Indonesia, sebagai dasar penyusunan dan interpretasi proyeksi keuangan dalam analisis kelayakan finansial.\n"
                          . "4. Beberapa konsep teori akademik yang umum digunakan dalam penyusunan studi kelayakan seperti: *investment decision theory, project life cycle theory, supply demand theory, proter’s five forces, time value of money, discounted cash flow, capital structure theory, externality theory, sustainable development* dan lainnya.\n"
                          . "5. **Peraturan perundang-undangan di bidang perindustrian dan industri hasil tembakau**, termasuk ketentuan mengenai perizinan berusaha berbasis risiko, perizinan dan kegiatan industri, cukai hasil tembakau, ketentuan mengenai pabrik hasil tembakau, serta ketentuan teknis lainnya yang diterbitkan oleh kementerian/lembaga yang berwenang.\n"
                          . "6. **Peraturan di bidang lingkungan hidup**, termasuk ketentuan mengenai persetujuan lingkungan, AMDAL atau UKL-UPL sesuai skala dan karakteristik kegiatan, pengelolaan limbah, emisi, air limbah, serta ketentuan lingkungan lainnya yang relevan dengan pembangunan dan operasional pabrik.\n"
                          . "7. **Peraturan di bidang tata ruang, pertanahan, bangunan gedung dan infrastruktur**, termasuk kesesuaian kegiatan pemanfaatan ruang, persetujuan bangunan gedung, sertifikat laik fungsi, ketentuan kawasan industri apabila relevan, serta ketentuan pemerintah daerah yang berlaku pada lokasi proyek.\n"
                          . "8. **Peraturan di bidang ketenagakerjaan serta keselamatan dan kesehatan kerja (K3)** yang relevan dengan tahap pembangunan maupun tahap operasional pabrik.\n"
                          . "9. Peraturan, standar teknis, dan pedoman lainnya yang diterbitkan oleh **Kementerian Perindustrian, Kementerian Keuangan/Direktorat Jenderal Bea dan Cukai, Kementerian Lingkungan Hidup, Kementerian Ketenagakerjaan, Kementerian ATR/BPN, pemerintah daerah**, dan instansi berwenang lainnya, sepanjang relevan dengan pembangunan dan kegiatan operasional pabrik pengolahan tembakau.\n"
                          . "a. **Hak & Kewajiban**\n"
                          . "10. Hak dan Kewajiban Pemberi Tugas:\n"
                          . "a. menerima laporan lengkap meliputi: resume, draf dan final laporan\n"
                          . "b. meminta diskusi dan penjelasan\n"
                          . "c. membayar biaya jasa\n"
                          . "d. memberikan keterangan, data dan dokumen yang diminta penilai\n"
                          . "11. Kewajiban konsultan:\n"
                          . "e. mengerjakan studi sesuai lingkup penugasan dan tujuannya\n"
                          . "f. meminta keterangan manajemen, data dan dokumen yang diperlukan\n"
                          . "g. melakukan review, analisa dan perhitungan\n"
                          . "h. menyerahkan resume, draft dan final laporan\n"
                          . "i. melakukan diskusi dengan manajemen\n"
                          . "j. menerima biaya jasa penyusunan studi kelayakan\n"
                          . "k. memberikan opini dan analisa yang independent\n"
                          . "b. **Jenis Mata Uang**\n"
                          . "Jenis Mata Uang yang akan digunakan dalam laporan studi kelaakan ini adalah Rupiah (Rp).\n"
                          . "a. **Benturan Kepentingan**\n"
                          . "Kami sebagai penilai menyatakan bahwa status kami adalah sebagai penilai independen. Kami menyatakan bahwa tidak ada keterlibatan material dan benturan kepentingan baik yang aktual maupun bersifat potensial terhadap objek studi.\n"
                          . "a. **Kondisi Pembatas Konsultan**\n"
                          . "Berdasarkan identifikasi awal, terdapat kondisi pembatas dalam pelaksanaan konsultan yaitu sebatas pada tujuan dan ruang lingkup.\n",
            ],
            'tor_maksud_dan_tujuan_studi_kelayakan_proyek' => [
                'judul' => "MAKSUD DAN TUJUAN STUDI KELAYAKAN PROYEK",
                'teks'  => "Maksud dan tujuan dari pekerjaan ini adalah untuk memberikan analisis dan opini profesional mengenai tingkat kelayakan Proyek Pembangunan Pabrik Pengolahan Tembakau yang sedang dilaksanakan oleh :klien, dengan mempertimbangkan berbagai aspek yang berpengaruh terhadap penyelesaian pembangunan, kebutuhan pendanaan, kesiapan operasional, dan keberlanjutan Proyek, meliputi:\n"
                          . "1. Latar Belakang dan Gambaran Umum Proyek\n"
                          . "2. Aspek Legalitas dan Perizinan\n"
                          . "3. Aspek Teknis\n"
                          . "4. Aspek Pasar dan Pemasaran\n"
                          . "5. Aspek Teknis dan Operasional\n"
                          . "6. Aspek Manajemen & Sumber Daya Manusia\n"
                          . "7. Analisis Aspek Sosial, Lingkungan, dan Keberlanjutan (ESG)\n"
                          . "8. Aspek Keuangan dan Kelayakan Investasi\n"
                          . "9. Analisis Risiko dan Sensitivitas\n"
                          . "10. Kesimpulan & Rekomendasi\n",
            ],
            'tor_materi_dan_kerangka_laporan_studi_kelayakan' => [
                'judul' => "MATERI DAN KERANGKA LAPORAN STUDI KELAYAKAN",
                'teks'  => "Materi yang dibahas dalam Studi Kelayakan disusun menurut kerangka berikut, mencakup beberapa aspek utama yang relevan dengan Proyek Pembangunan Pabrik Pengolahan Tembakau :klien.",
            ],
            'tor_bab_latar_belakang_gambara_umum_proyek' => [
                'judul' => "BAB I Latar belakang & gambara umum proyek",
                'teks'  => "A. Latar Belakang\n"
                          . "\n"
                          . "B. Identifikasi Masalah\n"
                          . "\n"
                          . "C. Maksud dan Tujuan Penyusunan Studi Kelayakan Proyek\n"
                          . "\n"
                          . "D. Gambaran Umum Proyek\n"
                          . "\n"
                          . "E. Metode Penyusunan Studi Kelayakan Proyek\n"
                          . "\n"
                          . "F. Sistematika Pembahasan\n",
            ],
            'tor_bab_aspek_legalitas_perijinan' => [
                'judul' => "BAB II ASPEK LEGALITAS & perijinan",
                'teks'  => "A. Kondisi Perusahaan\n"
                          . "\n"
                          . "A. Legalitas dan Perizinan Perusahaan\\\n"
                          . "\n"
                          . "B. Legalitas dan Perizinan Proyek\n",
            ],
            'tor_bab_aspek_pasar_dan_pemasaran' => [
                'judul' => "BAB III ASPEK PASAR DAN PEMASARAN",
                'teks'  => "A. Kondisi Makro Ekonomi dan Industri\n"
                          . "\n"
                          . "B. Analisis Permintaan dan Penawaran\n"
                          . "\n"
                          . "C. Analisis Persaingan\n"
                          . "\n"
                          . "D. Strategi Pemasaran\n"
                          . "\n"
                          . "E. Analisis SWOT dan Prospek Pasar\n",
            ],
            'tor_bab_aspek_teknis_operasional' => [
                'judul' => "BAB IV aspek teknis & operasional",
                'teks'  => "A. Lokasi dan Kondisi Proyek\n"
                          . "\n"
                          . "B. Progres Pembangunan Proyek\n"
                          . "\n"
                          . "C. Bangunan, Mesin dan Peralatan\n"
                          . "\n"
                          . "D. Proses Produksi dan Kapasitas\n"
                          . "\n"
                          . "E. Utilitas dan Infrastruktur\n"
                          . "\n"
                          . "F. Evaluasi Penyelesaian Proyek\n",
            ],
            'tor_bab_aspek_manajemen_dan_sumber_daya_manusia' => [
                'judul' => "BAB V aspek MANAJEMEN DAN sumber daya manusia",
                'teks'  => "A. Struktur Organisasi Perusahaan dan Proyek\n"
                          . "\n"
                          . "B. Struktur Organisasi Operasional Pabrik\n"
                          . "\n"
                          . "C. Pembagian Tugas, Wewenang, dan Tanggung Jawab\n"
                          . "\n"
                          . "D. Kebutuhan dan Ketersediaan Tenaga Kerja\n"
                          . "\n"
                          . "E. Kompetensi dan Tenaga Ahli\n"
                          . "\n"
                          . "F. Sistem Rekrutmen dan Pelatihan\n"
                          . "\n"
                          . "G. Sistem Operasional dan Standar Operasional Prosedur (SOP)\n"
                          . "\n"
                          . "H. Kesiapan Organisasi dan SDM dalam Pengoperasian Pabrik\n",
            ],
            'tor_bab_analisis_aspek_sosial_lingkungan_dan_keberla' => [
                'judul' => "BAB VI Analisis Aspek Sosial, Lingkungan, dan Keberlanjutan (ESG)",
                'teks'  => "A. Aspek Lingkungan\n"
                          . "\n"
                          . "B. Aspek Sosial\n"
                          . "\n"
                          . "C. Aspek Tata Kelola (*Governance*)\n"
                          . "\n"
                          . "D. Keberlanjutan Proyek\n",
            ],
            'tor_bab_aspek_keuangan_kelayakan_investasi' => [
                'judul' => "BAB VII ASPEK KEUANGAN & kelayakan investasi",
                'teks'  => "A. Asumsi Dasar Proyeksi\n"
                          . "\n"
                          . "B. Investasi Proyek\n"
                          . "\n"
                          . "C. Sumber dan Struktur Pendanaan\n"
                          . "\n"
                          . "D. Proyeksi Pendapatan dan Biaya\n"
                          . "\n"
                          . "E. Proyeksi Laporan Keuangan\n"
                          . "\n"
                          . "F. Analisis Kelayakan Investasi\n",
            ],
            'tor_bab_analisa_resiko_sensitivitas' => [
                'judul' => "BAB VIII Analisa resiko & sensitivitas",
                'teks'  => "A. Identifikasi Risiko Proyek\n"
                          . "\n"
                          . "B. Analisis Sensitivitas\n"
                          . "\n"
                          . "C. Analisis Skenario\n"
                          . "\n"
                          . "D. Mitigasi Risiko\n",
            ],
            'tor_bab_analisa_resiko_sensitivitas_2' => [
                'judul' => "bab ix Analisa resiko & sensitivitas",
                'teks'  => "A. Kesimpulan\n"
                          . "\n"
                          . "B. Rekomendasi\n",
            ],
            'tor_metodologi_pelaksanaan' => [
                'judul' => "Metodologi Pelaksanaan",
                'teks'  => "Pekerjaan dilakukan secara terstruktur melalui tahapan:\n"
                          . "1. Studi pustaka dan penelaahan data sekunder;\n"
                          . "2. Pengumpulan data primer melalui wawancara dan observasi lapangan;\n"
                          . "3. Analisis kualitatif dan kuantitatif;\n"
                          . "4. Pemodelan keuangan dan analisis sensitivitas;\n"
                          . "5. Penyusunan laporan awal (draft), diskusi, dan finalisasi laporan.\n"
                          . "Seluruh analisis dilakukan berdasarkan data yang diberikan oleh manajemen dan sumber lain yang dianggap wajar serta dapat dipercaya.\n",
            ],
            'tor_jangka_waktu_penyerahan' => [
                'judul' => "JANGKA WAKTU PENYERAHAN",
                'teks'  => "Laporan studi kelayakan proyek akan kami serahkan dalam jangka waktu 21 (dua puluh satu) hari kerja terhitung setelah datadata yang lengkap terima dilakukan sebanyak 2 (dua) buku, dan disusun dalam Bahasa Indonesia. Adapun jadwal pelaksanaan pengerjaan penyusunan studi kelayakan sebagai berikut:",
            ],
            'tor_pekerjaan_tambahan' => [
                'judul' => "PEKERJAAN TAMBAHAN",
                'teks'  => "Apabila pekerjaan penyusunan Studi kelayakan Proyek yang dilakukan dan mengalami perubahan atas materi Studi kelayakan Proyek yang dilakukan oleh Pemberi Tugas, tidak sesuai dengan yang tercantum pada *Terms of Reference* yang terlampir bersama Surat Kontrak, maka Konsultan berhak atas tambahan *fee*, yang besarannya disesuaikan atas kesepakatan.",
            ],
            'tor_pembatalan_penugasan' => [
                'judul' => "PEMBATALAN PENUGASAN",
                'teks'  => "Pembatalan penugasan yang dilakukan oleh Pemberi Tugas tidak membebaskan Pemberi Tugas dari kewajibankewajiban terhadap Konsultan. Tujuan Studi kelayakan Proyek tidak ada hubungan ataupun kepentingan dengan pekerjaan Studi kelayakan Proyek yang dilakukan oleh Konsultan dan tidak dapat dijadikan alasan Pemberi Tugas untuk Pembatalan Penugasan.",
            ],
            'tor_penutup' => [
                'judul' => "PENUTUP",
                'teks'  => "*Terms of Reference (TOR)* penugasan Studi kelayakan ini dibuat dan merupakan satu kesatuan yang tidak dapat dipisahkan dengan Surat Kontrak serta mengikat kedua belah pihak.",
            ],
        ],
        'lampiran2' => [
            'lampiran2_aspek_legalitas' => [
                'judul' => "ASPEK LEGALITAS:",
                'teks'  => "a. Copy Akta Pendirian Perusahaan dan seluruh Akta Perubahan terakhir;\n"
                          . "b. Copy SK Pengesahan/Persetujuan/Penerimaan Pemberitahuan dari Kementerian Hukum;\n"
                          . "c. Copy NPWP dan Nomor Induk Berusaha (NIB);\n"
                          . "d. Data KBLI dan perizinan berusaha perusahaan melalui OSS-RBA;\n"
                          . "e. Copy daftar/susunan pemegang saham terakhir;\n"
                          . "f. Copy susunan Direksi dan Dewan Komisaris terakhir;\n"
                          . "g. Copy sertifikat tanah dan/atau dokumen penguasaan lahan lokasi proyek;\n"
                          . "h. Copy dokumen Kesesuaian Kegiatan Pemanfaatan Ruang (KKPR) dan/atau dokumen tata ruang lainnya;\n"
                          . "i. Copy Persetujuan Bangunan Gedung (PBG) dan dokumen teknis bangunan lainnya;\n"
                          . "j. Copy dokumen Persetujuan Lingkungan, AMDAL dan/atau UKL-UPL sesuai ketentuan yang berlaku;\n"
                          . "k. Copy perizinan/persetujuan teknis terkait pengelolaan air limbah, emisi, limbah B3 dan lingkungan lainnya, apabila ada;\n"
                          . "l. Copy perizinan industri dan perizinan khusus terkait kegiatan pengolahan/industri hasil tembakau;\n"
                          . "m. Copy perizinan dan/atau dokumen terkait cukai hasil tembakau, apabila telah tersedia/relevan;\n"
                          . "n. Copy perizinan penggunaan air tanah, ketenagalistrikan, proteksi kebakaran, dan utilitas lainnya, apabila relevan;\n"
                          . "o. Copy perjanjian dengan kontraktor, pemasok mesin, pemasok bahan baku, *off-taker*/pembeli, investor dan/atau pihak lainnya yang berkaitan dengan proyek;\n"
                          . "p. Dokumen legalitas dan perizinan lainnya yang berkaitan dengan pembangunan dan operasional pabrik.\n",
            ],
            'lampiran2_aspek_pasar_dan_pemasaran' => [
                'judul' => "ASPEK pasar dan PEMASARAN",
                'teks'  => "a. Profil produk yang akan dihasilkan oleh pabrik;\n"
                          . "b. Jenis, spesifikasi dan kualitas produk;\n"
                          . "c. Target pasar dan segmen konsumen;\n"
                          . "d. Data historis penjualan perusahaan/grup, apabila tersedia;\n"
                          . "e. Rencana volume penjualan selama periode proyeksi;\n"
                          . "f. Data dan asumsi harga jual produk;\n"
                          . "g. Daftar pelanggan eksisting dan calon pelanggan;\n"
                          . "h. Kontrak, PO, LOI, MoU dan/atau perjanjian dengan *off-taker*/pembeli, apabila tersedia;\n"
                          . "i. Data wilayah pemasaran dan jaringan distribusi;\n"
                          . "j. Data pesaing utama dan produk substitusi;\n"
                          . "k. Data pangsa pasar perusahaan dan/atau target pangsa pasar;\n"
                          . "l. Strategi pemasaran, distribusi dan penjualan;\n"
                          . "m. Kebijakan diskon, termin pembayaran dan kredit pelanggan;\n"
                          . "n. Kajian pasar yang pernah dibuat sebelumnya, apabila tersedia;\n"
                          . "o. Data pendukung lainnya terkait prospek industri dan pemasaran produk.\n",
            ],
            'lampiran2_aspek_teknis_operasional' => [
                'judul' => "ASPEK TEKNIS & OPERASIONAL",
                'teks'  => "a. Data lokasi dan luas proyek;\n"
                          . "b. *Site plan*, *master plan* dan layout pabrik;\n"
                          . "c. Gambar teknis/*engineering drawing* bangunan dan fasilitas;\n"
                          . "d. Rencana Anggaran Biaya (RAB)/BOQ pembangunan proyek;\n"
                          . "e. Kontrak pekerjaan konstruksi dan addendum, apabila ada;\n"
                          . "f. Jadwal pelaksanaan pembangunan/*master schedule* proyek;\n"
                          . "g. Laporan progres pembangunan terakhir;\n"
                          . "h. Data progres fisik per pekerjaan;\n"
                          . "i. Daftar bangunan dan fasilitas yang telah dan akan dibangun;\n"
                          . "j. Daftar mesin dan peralatan produksi;\n"
                          . "k. Spesifikasi teknis, kapasitas, merek, tipe, tahun dan negara pembuat mesin;\n"
                          . "l. Quotation, invoice, purchase order dan kontrak pembelian mesin;\n"
                          . "m. Jadwal pengiriman, instalasi, *testing* dan *commissioning* mesin;\n"
                          . "n. Diagram/alur proses produksi (*production flow process*);\n"
                          . "o. Kapasitas produksi terpasang dan kapasitas produksi yang direncanakan;\n"
                          . "p. Rencana utilisasi kapasitas produksi;\n"
                          . "q. Kebutuhan bahan baku tembakau dan bahan penolong;\n"
                          . "r. Spesifikasi dan sumber bahan baku;\n"
                          . "s. Data kebutuhan listrik, air, bahan bakar dan utilitas lainnya;\n"
                          . "t. Data fasilitas gudang bahan baku, barang dalam proses dan barang jadi;\n"
                          . "u. Data fasilitas pengolahan limbah, emisi dan utilitas lingkungan;\n"
                          . "v. Rencana pemeliharaan mesin dan fasilitas;\n"
                          . "w. Rencana operasional pabrik setelah *commissioning*;\n"
                          . "x. SOP produksi dan operasional, apabila telah tersedia.\n",
            ],
            'lampiran2_data_progres_proyek_cost_to_complate' => [
                'judul' => "DATA PROGRES PROYEK COST TO COMPLATE",
                'teks'  => "a. Total anggaran investasi proyek yang telah disetujui;\n"
                          . "b. Rincian investasi proyek sejak awal pembangunan;\n"
                          . "c. Realisasi investasi sampai dengan tanggal kajian;\n"
                          . "d. Rincian pembayaran kepada kontraktor dan pemasok;\n"
                          . "e. Progres fisik dibandingkan dengan progres keuangan;\n"
                          . "f. Daftar pekerjaan yang telah selesai;\n"
                          . "g. Daftar pekerjaan yang sedang berjalan;\n"
                          . "h. Daftar pekerjaan yang belum dilaksanakan;\n"
                          . "i. Nilai kontrak awal dan seluruh addendum pekerjaan;\n"
                          . "j. Estimasi biaya penyelesaian pembangunan (*cost to complete*);\n"
                          . "k. Estimasi biaya mesin/peralatan yang belum dibeli atau dibayar;\n"
                          . "l. Estimasi kebutuhan biaya *commissioning* dan *pre-operating*;\n"
                          . "m. Estimasi kebutuhan modal kerja awal;\n"
                          . "n. Jadwal penyelesaian pembangunan terakhir;\n"
                          . "o. Target *commercial operation date* (COD);\n"
                          . "p. Penjelasan atas keterlambatan dan/atau *cost overrun*, apabila ada.\n",
            ],
            'lampiran2_aspek_manajemen_sumber_daya_manusia' => [
                'judul' => "ASPEK MANAJEMEN & SUMBER DAYA MANUSIA",
                'teks'  => "a. Struktur organisasi perusahaan saat ini;\n"
                          . "b. Struktur organisasi proyek pembangunan;\n"
                          . "c. Struktur organisasi yang direncanakan pada saat pabrik beroperasi;\n"
                          . "d. Profil manajemen dan tenaga ahli utama;\n"
                          . "e. Jumlah tenaga kerja eksisting;\n"
                          . "f. Rencana kebutuhan tenaga kerja operasional;\n"
                          . "g. Kualifikasi dan kompetensi tenaga kerja;\n"
                          . "h. Struktur gaji, tunjangan dan biaya tenaga kerja;\n"
                          . "i. SOP manajemen dan operasional perusahaan;\n"
                          . "j. Data sistem keselamatan dan kesehatan kerja (K3).\n",
            ],
            'lampiran2_aspek_aspek_sosial_lingkungan_dan_keberlanjutan_' => [
                'judul' => "ASPEK ASPEK SOSIAL, LINGKUNGAN DAN KEBERLANJUTAN (ESG)",
                'teks'  => "a. *Dokumen AMDAL/UKL-UPL dan Persetujuan Lingkungan;*\n"
                          . "b. Data konsumsi energi;\n"
                          . "c. Kebijakan ESG/keberlanjutan perusahaan, apabila tersedia;\n"
                          . "d. Program CSR/pemberdayaan masyarakat, apabila tersedia;\n"
                          . "e. Data pengaduan atau permasalahan dengan masyarakat sekitar, apabila ada.\n",
            ],
            'lampiran2_aspek_keuangan_kelayakan_investasi' => [
                'judul' => "ASPEK KEUANGAN & KELAYAKAN INVESTASI",
                'teks'  => "a. *Laporan keuangan audited perusahaan minimal 3 tahun terakhir, apabila tersedia;*\n"
                          . "b. *Rencana sisa investasi/remaining CAPEX;*\n"
                          . "c. Rencana kebutuhan modal kerja;\n"
                          . "d. Sumber dan struktur pendanaan proyek;\n"
                          . "e. Porsi modal sendiri dan pinjaman;\n"
                          . "f. *Term sheet, offering letter dan/atau indikasi pembiayaan dari bank, apabila tersedia;*\n"
                          . "g. *Plafon kredit, suku bunga, tenor, grace period dan jadwal pembayaran pinjaman;*\n"
                          . "h. *Rencana penarikan fasilitas pembiayaan;*\n"
                          . "i. *Business Plan*\n",
            ],
        ],
    ],

    'pengawasan' => [
        'perihal'     => 'Proposal Penawaran Jasa Pengawasan Proyek Pembangunan ---- :klien',
        'pembuka'     => 'Menindaklanjuti permintaan ----- untuk penyusunan Laporan Pengawasan *(Monitoring)* proyek pembangunan ----- yang diprakarsai oleh :klien, bersama ini kami dari KJPP Sugianto Prasodjo dan Rekan menyampaikan proposal penawaran biaya jasa penyusunan Laporan Pengawasan dilengkapi dengan Kerangka Acuan Kerja (KAK), sebagai berikut:',
        'biaya_intro' => 'Biaya jasa profesional pengawasan Proyek Pembangunan Konstruksi untuk 1 (satu) kali kunjungan adalah sebesar:',
        'termin_first' => 'Tahap :tahap : :pct% dari biaya jasa sebesar **:rp (:terbilang),** dibayarkan pada saat penandatanganan Surat Perjanjian Kerja (SPK)',
        'termin_mid' => 'Tahap :tahap : :pct% dari biaya jasa sebesar **:rp (:terbilang),** dibayarkan setelah draft laporan diserahkan.',
        'termin_last' => 'Tahap :tahap : :pct% dari biaya jasa sebesar **:rp (:terbilang),** dibayarkan sebelum final report diserahkan.',
        'termin_tahap' => true,
        'kalimat_surat_tugas' => 'melakukan Jasa Pengawasan Proyek Pembangunan atas nama :klien.',
        'judul_biaya' => 'Biaya Jasa',
        'judul_tor'   => 'Penyusunan Laporan Pengawasan',
        'bab' => [
            'ringkasan_proposal' => [
                'judul' => "Ringkasan Proposal",
                'teks'  => "Proposal ini disusun untuk memberikan gambaran menyeluruh mengenai ruang lingkup pekerjaan, pendekatan pengawasan, metodologi pelaksanaan, jadwal kegiatan, susunan tim pelaksana, serta estimasi biaya jasa dalam rangka pelaksanaan pekerjaan pengawasan proyek pembangunan ---- yang diprakarsai oleh **:klien** dan berlokasi di -----. Pekerjaan pengawasan ini bertujuan untuk memastikan bahwa pelaksanaan konstruksi berjalan sesuai dengan perencanaan, spesifikasi teknis, ketentuan kontrak, serta ketentuan lainnya yang berlaku.",
            ],
            'latar_belakang_dan_tujuan_pekerjaan' => [
                'judul' => "Latar Belakang dan Tujuan Pekerjaan",
                'teks'  => "Seiring dengan dilaksanakannya proyek pembangunan oleh **:klien**, diperlukan pelaksanaan pengawasan yang independen dan profesional guna memastikan bahwa seluruh tahapan pekerjaan konstruksi dilaksanakan sesuai dengan gambar rencana, spesifikasi teknis, jadwal pelaksanaan, serta standar mutu dan keselamatan kerja yang ditetapkan. Pengawasan yang memadai menjadi faktor penting dalam mengendalikan mutu, waktu, dan biaya pelaksanaan proyek, serta meminimalkan potensi penyimpangan dan risiko selama masa konstruksi.\n"
                          . "\n"
                          . "Tujuan dari pekerjaan pengawasan ini adalah untuk:\n"
                          . "1. Memastikan kesesuaian pelaksanaan pekerjaan konstruksi dengan dokumen perencanaan, spesifikasi teknis, dan ketentuan kontrak;\n"
                          . "2. Memantau pelaksanaan pembangunan agar berjalan sesuai dengan jadwal pelaksanaan serta anggaran proyek yang telah disusun oleh :klien dan disetujui oleh Pihak Bank.\n"
                          . "3. Menyampaikan masukan dan rekomendasi kepada Pihak Bank dan :klien terkait hasil pemantauan pelaksanaan proyek dalam rangka pengamanan fasilitas kredit yang diberikan oleh Pihak :pengguna_laporan.\n"
                          . "4. Menyampaikan laporan berkala kepada Pemberi Tugas yang memuat informasi mengenai progres fisik dan realisasi biaya pelaksanaan proyek.\n",
            ],
            'penjelasan_status_penilai_konsultan' => [
                'judul' => "Penjelasan Status Penilai (Konsultan)",
                'teks'  => "Penilai Publik dalam pekerjaan bertindak sebagai konsultan yang bertanggung jawab sekaligus yang bertanda tangan di dalam Laporan Pengawasan Proyek ini adalah **Nama Penilai Publik, MAPPI (Cert.),** merupakan Penilai Publik Properti/Bisnis/Properti dan Bisnis dengan lzin Penilai Publik **No. P-x.xx.xxxxx** berdasarkan Surat Keputusan Menteri Keuangan Republik Indonesia Nomor **xxx/xx/xx/xxx** tanggal **xx xxx xxxx**.\n"
                          . "\n"
                          . "Penilai bertindak atas nama **Kantor Jasa Penilai Publik SUGIANTO PRASODJO DAN REKAN** memiliki **lzin Usaha resmi dari Kementerian Keuangan Republik Indonesia No. 2.15.0131** berdasarkan **Kepmenkeu No. 722/KM.1/2015** tanggal **09 September 2015** dari Menteri Keuangan Republik Indonesia**.** KJPP Sugianto Prasodjo dan Rekan adalah perusahaan penilai independen yang terdaftar di Masyarakat Profesi Penilai Indonesia (MAPPI) dan terdaftar di Otoritas Jasa Keuangan/OJK (d/h Bapepam-LK) berdasarkan **Surat Tanda Terdaftar Profesi Penunjang Pasar Modal No. S-859/PM.223/2015** tanggal **17 November 2015**.\n",
            ],
            'pemberi_tugas_dan_pengguna_laporan' => [
                'judul' => "Pemberi Tugas dan Pengguna Laporan",
                'teks'  => "Pemberi Tugas dan sekaligus Pengguna Laporan dalam pekerjaan ini adalah **:klien** dan **:pengguna_laporan**.",
            ],
            'ruang_lingkup_pekerjaan' => [
                'judul' => "Ruang Lingkup Pekerjaan",
                'teks'  => "Pekerjaan yang dimaksud adalah pelaksanaan monitoring/pengawasan pelaksanaan proyek pembangunan yang diprakarsai oleh :klien. Kegiatan monitoring ini bertujuan untuk memberikan pemantauan independen atas perkembangan pelaksanaan proyek, khususnya terkait progres fisik dan realisasi biaya, sebagai dasar informasi bagi Pemberi Tugas dan/atau Pihak Pendana dalam rangka pengendalian dan pengamanan pelaksanaan proyek. Adapun ruang lingkup pekerjaan monitoring meliputi kegiatan sebagai berikut:\n"
                          . "a. **Melakukan pemantauan pelaksanaan proyek**, meliputi pemantauan kondisi eksisting proyek di lapangan, kesesuaian pelaksanaan pekerjaan dengan dokumen perencanaan, spesifikasi teknis, dan ketentuan kontrak, berdasarkan observasi visual dan dokumen pendukung yang tersedia.\n"
                          . "b. **Melakukan pemantauan progres fisik pekerjaan**, meliputi penilaian capaian progres fisik proyek secara periodik dibandingkan dengan rencana jadwal pelaksanaan (*time schedule*/kurva-S) yang disampaikan oleh pihak pelaksana proyek.\n"
                          . "c. **Melakukan pemantauan realisasi biaya pelaksanaan proyek**, meliputi penelaahan realisasi biaya yang telah dikeluarkan dan/atau diajukan dibandingkan dengan anggaran proyek yang telah disetujui, berdasarkan data dan dokumen yang disampaikan oleh :klien dan/atau kontraktor.\n"
                          . "d. **Melakukan pemantauan aspek administratif dan dokumentasi proyek**, meliputi penelaahan dokumen pendukung pelaksanaan proyek, seperti laporan pelaksanaan, berita acara, dan dokumen administratif lain yang relevan dengan kegiatan monitoring.\n"
                          . "e. **Mengidentifikasi potensi deviasi dan risiko pelaksanaan proyek,** Meliputi identifikasi awal terhadap potensi keterlambatan, pembengkakan biaya, atau penyimpangan pelaksanaan proyek berdasarkan hasil pemantauan, serta menyampaikan catatan dan pandangan umum mengenai hal-hal yang perlu mendapat perhatian.\n"
                          . "f. **Menyampaikan laporan monitoring secara berkala,** Meliputi penyusunan dan penyampaian laporan monitoring yang memuat ringkasan kondisi proyek, progres fisik, realisasi biaya, serta catatan hasil pemantauan kepada Pemberi Tugas dan/atau Pihak pemberi fasilitas kredit.\n"
                          . "g. **Menyusun ringkasan hasil monitoring untuk kepentingan Pihak pemberi fasilitas kredit**, sebagai bahan informasi dan komunikasi dalam rangka pengamanan fasilitas pembiayaan proyek, tanpa bersifat rekomendatif maupun pengambilan keputusan teknis.\n",
            ],
            'metodologi_pelaksanaan' => [
                'judul' => "Metodologi Pelaksanaan",
                'teks'  => "Pekerjaan monitoring ini dilaksanakan dengan metodologi terstruktur yang meliputi pengumpulan data melalui kunjungan lapangan, penelaahan dokumen yang disediakan oleh pihak terkait, klarifikasi apabila diperlukan, serta penyusunan laporan monitoring dalam bentuk laporan periodik dan laporan akhir sesuai dengan ketentuan penugasan.",
            ],
            'peraturan_standar_dan_konsep' => [
                'judul' => "Peraturan, Standar dan Konsep",
                'teks'  => "Dalam Menyusun laporan Pengawasan, konsultan mengacu kepada peraturan dan standar antara lain:\n"
                          . "a. Kode Etik (KEPI) & Standar Penilaian Indonesia (SPI) **Edisi VII Tahun 2018** dan **Edisi VII Revisi Tahun 2020 SPI 350** tentang jasa konsultasi.\n"
                          . "b. Peraturan Menteri Keuangan Republik Indonesia Nomor 101/PMK.01/2014 tentang Penilai Publik dan Keputusan Menteri Keuangan Nomor 722/KM.1/2015 tentang Pemberian Izin KJPP dan Penilai Publik, sebagai dasar hukum pelaksanaan penugasan.\n"
                          . "c. Peraturan perundang-undangan, kebijakan, serta pedoman teknis lain yang relevan dengan karakteristik objek pengawasan.\n"
                          . "d. Beberapa konsep teori akademik yang umum digunakan dalam penyusunan laporan pengawasan.\n",
            ],
            'struktur_laporan_pengawasan' => [
                'judul' => "Struktur Laporan Pengawasan",
                'teks'  => "a. Pendahuluan\n"
                          . "b. Metodologi & Pengawasan\n"
                          . "c. Gambaran Umum Proyek\n"
                          . "d. Program Perencanaan dan Pelaksanaan (*Baseline* Proyek)\n"
                          . "e. Perkembangan Pelaksanaan Proyek (Fisik dan Biaya)\n"
                          . "f. Rencana Kerja Berikutnya\n"
                          . "g. Kesimpulan dan Saran\n",
            ],
            'asumsi_batasan' => [
                'judul' => "Asumsi & Batasan",
                'teks'  => "a. Analisis dan penilaian dalam laporan ini disusun berdasarkan data dan informasi yang disampaikan oleh manajemen proyek, kontraktor, dan/atau pihak terkait lainnya, yang diasumsikan benar, lengkap, dan dapat dipertanggungjawabkan. Konsultan tidak bertanggung jawab atas ketidakakuratan data yang berada di luar kendali konsultan.\n"
                          . "b. Sumber informasi tambahan diperoleh dari sumber yang dianggap andal tanpa dilakukan verifikasi atau konfirmasi independen oleh konsultan.\n"
                          . "c. Laporan monitoring ini bersifat informatif sebagai hasil pemantauan dan tidak merupakan instruksi, persetujuan teknis, maupun keputusan manajerial, serta tidak bersifat mengikat.\n"
                          . "d. Konsultan tidak melakukan pekerjaan due diligence, audit teknis, audit keuangan, maupun audit hukum, sehingga penilaian terbatas pada telaah dokumen dan observasi visual.\n"
                          . "e. Konsultan tidak bertanggung jawab atas perubahan kondisi proyek yang terjadi setelah tanggal laporan ini disusun.\n"
                          . "f. Konsultan tidak bertanggung jawab atas kegagalan pelaksanaan proyek yang disebabkan oleh faktor di luar ruang lingkup pengawasan/monitoring, termasuk namun tidak terbatas pada kesalahan pelaksanaan konstruksi, perubahan desain, keterlambatan material, atau kebijakan pihak ketiga.\n"
                          . "g. Laporan ini disusun khusus untuk kepentingan Pemberi Tugas dan/atau Pihak pemberi fasilitas kredit sesuai dengan penugasan, dan tidak dimaksudkan untuk digunakan oleh pihak lain tanpa persetujuan tertulis dari konsultan.\n",
            ],
            'tim_pelaksana' => [
                'judul' => "Tim Pelaksana",
                'teks'  => "Dalam melakukan Pekerjaan Pengawasan Proyek Pembangunan ---sebutkan proyek apa--- yang diprakarsai oleh :klien, tenaga ahli yang terlibat secara langsung dan tidak langsung adalah sebagai berikut:\n"
                          . "\n"
                          . "Tenaga Ahli\n",
            ],
            'benturan_kepentingan' => [
                'judul' => "Benturan Kepentingan",
                'teks'  => "Kami sebagai penilai dalam hal ini bertindak sebagai konsultan menyatakan bahwa status kami bertindak secara independen. Kami menyatakan bahwa tidak ada keterlibatan material dan benturan kepentingan baik yang aktual maupun bersifat potensial terhadap objek pengawasan.",
            ],
            'kondisi_pembatas_konsultan' => [
                'judul' => "Kondisi Pembatas Konsultan",
                'teks'  => "Berdasarkan identifikasi awal, terdapat kondisi pembatas dalam pelaksanaan konsultan yaitu sebatas pada tujuan dan ruang lingkup.",
            ],
            'prosedur_pelaksanaan_penugasan' => [
                'judul' => "Prosedur Pelaksanaan Penugasan",
                'teks'  => "Penugasan pengawasan ini akan dilakukan menurut prosedur dengan tahapan-tahapan sebagai berikut :\n"
                          . "a. Persiapan dan pengumpulan data awal.\n"
                          . "b. Pemeriksaan dan penelitian lapangan, untuk memperoleh data akurat tentang spesifikasi dan kondisi sebenarnya dari objek penugasan, sebagai dasar menyusun berbagai aspek yang akan analisa.\n"
                          . "c. Melakukan analisa dan perhitungan rencana proyek.\n"
                          . "d. Penyusunan hasil Analisa.\n"
                          . "e. Penyusunan laporan final pengawassan.\n",
            ],
            'pelaporan' => [
                'judul' => "Pelaporan",
                'teks'  => "Laporan final akan diserahkan dalam jangka waktu --- (----) hari kerja, dengan jangka waktu pekerjaan sebagaimana tercantum pada Lampiran 1 Kerangka Acuan Kerja (KAK)/*Term of Reference (ToR)* bagian XIII.",
            ],
            'pembatalan_penugasan' => [
                'judul' => "Pembatalan Penugasan",
                'teks'  => "Pembatalan penugasan secara sepihak oleh Pemberi Tugas tidak membebaskan Pemberi Tugas dari kewajiban-kewajiban terhadap Penilai. Tujuan penugasan tidak memiliki hubungan ataupun kepentingan dengan pekerjaan yang dilakukan oleh Penilai dan tidak dapat dijadikan alasan oleh Pemberi Tugas untuk pembatalan penugasan.",
            ],
            'pekerjaan_tambahan' => [
                'judul' => "Pekerjaan Tambahan",
                'teks'  => "Apabila pekerjaan penyusunan Laporan Pengawasan *(Monitoring Project)* yang dilakukan dan mengalami perubahan atas materi yang dilakukan oleh Pemberi Tugas, tidak sesuai dengan yang tercantum pada *Terms of Reference* (TOR) yang terlampir, maka Konsultan berhak atas tambahan *fee*, yang besarannya disesuaikan atas kesepakatan.",
            ],
            'kerahasiaan_informasi' => [
                'judul' => "Kerahasiaan Informasi",
                'teks'  => "KJPP Sugianto Prasodjo dan Rekan akan menjaga kerahasiaan informasi yang diterima dan hanya menyampaikan informasi tersebut pada karyawan dan kuasanya yang berkepentingan dan tidak membocorkan informasi meliputi namun tidak terbatas pada informasi tentang dan menyangkut bisnis, perencanaan, data keuangan dan lain-lain kepada pihak ketiga termasuk afiliasi dan subsidiari.\n"
                          . "\n"
                          . "Apabila proposal ini disetujui dan ditandatangani oleh Para Pihak, maka proposal ini berlaku sebagai Surat Perjanjian Kerja (SPK) yang efektif sejak tanggal penandatanganan, dengan ketentuan bahwa pelaksanaan pekerjaan mengacu pada Kerangka Acuan Kerja (TOR) yang menjadi satu kesatuan dan tidak terpisahkan dari proposal ini.\n"
                          . "\n"
                          . "Mohon penandatanganan dilakukan pada kolom persetujuan serta paraf pada setiap halaman. Apabila dokumen ini memuat barcode/QR Code KJPP Sugianto Prasodjo & Rekan, maka keabsahan dokumen dapat diverifikasi melalui pemindaian barcode/QR Code dan dinyatakan sah apabila data yang ditampilkan identik. Dalam hal dokumen tidak memuat barcode/QR Code, keabsahan ditentukan berdasarkan tanda tangan dan paraf Para Pihak serta kesesuaian identitas dokumen. Setiap perubahan, penggantian, atau penambahan halaman tanpa persetujuan tertulis dari KJPP Sugianto Prasodjo & Rekan dinyatakan tidak sah dan membatalkan keabsahan dokumen ini.\n",
            ],
        ],
        'tor' => [
            'tor_latar_belakang' => [
                'judul' => "Latar belakang",
                'teks'  => "Sehubungan dengan dilaksanakannya proyek pembangunan yang diprakarsai oleh :klien, diperlukan adanya pelaksanaan pengawasan/monitoring proyek secara independen guna memperoleh gambaran yang objektif mengenai perkembangan pelaksanaan proyek, khususnya terkait progres fisik dan realisasi biaya**.**\n"
                          . "\n"
                          . "Pengawasan/monitoring ini dimaksudkan sebagai sarana pemantauan dan pengendalian pelaksanaan proyek, serta sebagai bahan informasi bagi Pemberi Tugas dan/atau Pihak Pemberi Fasilitas Kredit dalam rangka pengamanan pelaksanaan proyek dan fasilitas pembiayaan yang diberikan.\n",
            ],
            'tor_maksud_dan_tujuan_pengawasan' => [
                'judul' => "maksud dan tujuan pengawasan",
                'teks'  => "Maksud dari pelaksanaan pekerjaan pengawasan ini adalah untuk menyediakan pemantauan independen dan objektif atas pelaksanaan proyek pembangunan, sehingga Pemberi Tugas dan/atau Pihak Pendana memperoleh gambaran yang memadai mengenai perkembangan pelaksanaan proyek, khususnya terkait progres fisik dan realisasi biaya, selama masa konstruksi.\n"
                          . "\n"
                          . "Tujuan dari pekerjaan pengawasan ini adalah untuk:\n"
                          . "1. Memastikan kesesuaian pelaksanaan pekerjaan konstruksi dengan dokumen perencanaan, spesifikasi teknis, dan ketentuan kontrak;\n"
                          . "2. Memantau pelaksanaan pembangunan agar berjalan sesuai dengan jadwal pelaksanaan serta anggaran proyek yang telah disusun oleh :klien dan disetujui oleh Pihak Bank.\n"
                          . "3. Menyampaikan masukan dan rekomendasi kepada Pihak Bank dan :klien terkait hasil pemantauan pelaksanaan proyek dalam rangka pengamanan fasilitas kredit yang diberikan oleh Pihak :pengguna_laporan.\n"
                          . "4. Menyampaikan laporan berkala kepada Pemberi Tugas yang memuat informasi mengenai progres fisik dan realisasi biaya pelaksanaan proyek.\n"
                          . "Untuk maksud dan tujuan tersebut pada butir II.1. dan II.2. Kerangka Acuan Kerja, KJPP SUGIANTO PRASODJO DAN REKAN mewakili PIHAK BANK akan melakukan verifikasi dan mengevaluasi segala sesuatu yang berhubungan dengan keterkaitan aspek teknis dan keuangan pelaksanaan pembangunan PROYEK dengan Perkembangan Pembiayaan PROYEK dan bila perlu menyampaikan rekomendasi kepada PIHAK BANK.\n",
            ],
            'tor_prinsip_prinsip_pengawasan' => [
                'judul' => "Prinsip-prinsip pengawasan",
                'teks'  => "Pelaksanaan pekerjaan pengawasan/monitoring proyek dilaksanakan berdasarkan prinsip-prinsip sebagai berikut:\n"
                          . "1. Pencegahan penggunaan fasilitas pembiayaan Pihak Bank di luar peruntukan proyek, dengan memastikan bahwa penggunaan dana proyek sesuai dengan rencana dan tujuan pembiayaan yang telah disetujui.\n"
                          . "2. Pengawasan didasarkan pada usulan dan rencana proyek yang disusun oleh :klien, yang telah memperoleh persetujuan dari Pihak Bank serta instansi pemerintah yang berwenang sesuai ketentuan peraturan perundang-undangan.\n"
                          . "3. Pelaksanaan pengawasan mengacu pada jadwal proyek dan rencana pembiayaan proyek yang disusun oleh :klien dan telah disetujui oleh Pihak Bank, sebagai dasar pembandingan terhadap realisasi pelaksanaan.\n"
                          . "4. Pengawasan realisasi biaya proyek dilakukan berdasarkan RAB (Rencana Anggaran Biaya) yang memuat rincian setiap jenis pekerjaan, sebagaimana disusun oleh :klien dan telah disetujui oleh Pihak Bank.\n"
                          . "5. Pengawasan dilakukan dengan mengacu pada laporan prestasi pekerjaan, khususnya yang dikaitkan dengan mekanisme pencairan kredit dan tahapan pembiayaan proyek.\n"
                          . "6. Pengawasan penggunaan dana proyek didukung oleh laporan realisasi dan bukti-bukti pendukung yang disampaikan oleh pihak terkait dan dapat dipertanggungjawabkan secara administratif.\n"
                          . "7. Pelaksanaan pengawasan memperhatikan ketentuan peraturan perundang-undangan yang berlaku, khususnya peraturan pemerintah yang mengatur pelaksanaan pembangunan proyek secara umum.\n",
            ],
            'tor_hak_dan_kewajiban' => [
                'judul' => "hak dan kewajiban",
                'teks'  => "1. Pemberi Tugas memiliki hak dan kewajiban sebagai berikut:\n"
                          . "a. Menerima laporan pengawasan secara lengkap yang meliputi ringkasan, draf, dan laporan final;\n"
                          . "b. Meminta diskusi, klarifikasi, dan penjelasan atas hasil pengawasan yang disampaikan;\n"
                          . "c. Meminta bantuan konsultan dalam memberikan penjelasan kepada pihak ketiga (termasuk auditor atau OJK) sepanjang terkait dengan hasil pengawasan;\n"
                          . "d. Membayarkan biaya jasa pengawasan sesuai dengan ketentuan yang disepakati;\n"
                          . "e. Menyediakan keterangan, data, dan dokumen yang diperlukan untuk kelancaran pelaksanaan pengawasan.\n"
                          . "2. Konsultan Pengawas memiliki hak dan kewajiban sebagai berikut:\n"
                          . "a. Melaksanakan pekerjaan pengawasan sesuai dengan ruang lingkup penugasan dan tujuan pengawasan;\n"
                          . "b. Meminta data, keterangan, dan dokumen yang diperlukan kepada manajemen proyek atau pihak terkait;\n"
                          . "c. Melakukan penelaahan, analisis, dan pemantauan atas progres fisik dan realisasi biaya proyek;\n"
                          . "d. Menyusun dan menyerahkan laporan pengawasan dalam bentuk ringkasan, draf, dan laporan final;\n"
                          . "e. Melakukan diskusi dan klarifikasi dengan manajemen proyek apabila diperlukan;\n"
                          . "f. Menerima pembayaran biaya jasa pengawasan sesuai perjanjian;\n"
                          . "g. Menyampaikan hasil pengawasan secara independen, objektif, dan profesional**,** tanpa dipengaruhi kepentingan pihak mana pun.\n",
            ],
            'tor_lingkup_pengawasan' => [
                'judul' => "lingkup pengawasan",
                'teks'  => "Lingkup pekerjaan pengawasan/monitoring proyek meliputi kegiatan sebagai berikut:\n"
                          . "1. Memeriksa dan menelaah penggunaan biaya pendahuluan (preliminary cost) yang telah dikeluarkan oleh proyek, baik yang bersumber dari dana *self financing* maupun dana yang berasal dari fasilitas kredit Bank, berdasarkan dokumen dan bukti pendukung yang disampaikan.\n"
                          . "2. Menelaah dokumen perizinan dan dokumen kerja sama yang terkait dengan pelaksanaan proyek, termasuk perjanjian kerja, kontrak dengan pihak ketiga, serta dokumen administratif lain yang relevan, sebatas pemeriksaan kelengkapan dan kesesuaian administratif.\n"
                          . "3. Melakukan pemantauan fisik terhadap pelaksanaan pembangunan dan pengadaan peralatan proyek, berdasarkan observasi visual pada saat kunjungan lapangan dan data pendukung yang tersedia.\n"
                          . "4. Menelaah kesesuaian ukuran, kapasitas, jenis, kualitas, dan kuantitas peralatan proyek, dengan mengacu pada dokumen perencanaan, spesifikasi teknis, dan RAB yang telah disetujui, tanpa melakukan pengujian teknis atau pemeriksaan laboratorium.\n"
                          . "5. Melakukan pemantauan realisasi biaya proyek, dengan membandingkan antara realisasi penggunaan dana dan rencana pembiayaan proyek sebagaimana tercantum dalam GBRKAB, RAB, atau dokumen anggaran lain yang telah disetujui.\n"
                          . "6. Mengevaluasi kemajuan pelaksanaan proyek, dengan membandingkan progres fisik dan realisasi biaya terhadap jadwal dan rencana yang telah ditetapkan, serta mengidentifikasi potensi hambatan, keterlambatan, atau penyimpangan yang teramati selama pelaksanaan proyek di lapangan.\n"
                          . "7. Menyusun dan menyampaikan catatan hasil pengawasan, termasuk pandangan dan rekomendasi secara umum kepada Pihak Bank, berdasarkan hasil pemantauan kemajuan pelaksanaan proyek, sebagai bahan pertimbangan dalam rangka pengamanan fasilitas pembiayaan proyek, tanpa bersifat instruktif maupun mengikat.\n",
            ],
            'tor_syarat_syarat_pelaksanaan_pengawasan' => [
                'judul' => "Syarat-syarat pelaksanaan pengawasan",
                'teks'  => "1. KJPP Sugianto Prasodjo dan Rekan akan melaksanakan penugasan pengawasan ini secara profesional dengan mengerahkan tenaga ahli yang kompeten, berpengalaman, dan sesuai dengan ruang lingkup penugasan yang diberikan.\n"
                          . "2. Dalam hal terdapat kebutuhan keahlian tertentu yang tidak tersedia secara internal pada KJPP Sugianto Prasodjo dan Rekan, maka KJPP Sugianto Prasodjo dan Rekan dapat melibatkan tenaga ahli atau konsultan lain sepanjang telah memperoleh persetujuan dari Pihak Bank, dengan tetap bertanggung jawab penuh atas pelaksanaan pekerjaan serta pembiayaan yang timbul dari keterlibatan pihak tersebut.\n"
                          . "3. KJPP Sugianto Prasodjo dan Rekan berkewajiban, atas permintaan Pihak Bank dan/atau PT Putera Karyasindo Prakarsa, untuk memberikan penjelasan, klarifikasi, dan pemaparan terkait isi laporan pengawasan, termasuk menunjukkan dasar perhitungan, analisis, serta data pendukung yang digunakan dalam rangka penyusunan laporan tersebut.\n"
                          . "4. KJPP Sugianto Prasodjo dan Rekan, dengan sepengetahuan PT Putera Karyasindo Prakarsa, dapat menyampaikan dan/atau mempresentasikan laporan pengawasan akhir apabila diminta atau diperlukan oleh Pihak Bank, sebagai bagian dari pelaksanaan penugasan.\n",
            ],
            'tor_objek_dan_lokasi_pengawasan' => [
                'judul' => "objek dan lokasi pengawasan",
                'teks'  => "1. Obyek pengawasan adalah Proyek Pembangunan ------ yang diprakarsai oleh :klien, sebagaimana tercantum dalam dokumen perencanaan dan spesifikasi proyek yang disusun oleh :klien dan telah memperoleh persetujuan dari Pihak Bank.\n"
                          . "2. Lokasi Objek Pengawasan Pembangunan adalah Proyek –pembangunan apa-- yang berlokasi di -----.\n",
            ],
            'tor_pelaksanaan_pengawasan' => [
                'judul' => "pelaksanaan pengawasan",
                'teks'  => "1. Tahapan Pra-Pelaksanaan\n"
                          . "a. :klien menyusun Garis Besar Rencana Kerja dan Anggaran Biaya (GBRKAB)/Rencana Anggaran Biaya (RAB) beserta jadwal pelaksanaan dan rencana pembiayaan proyek, yang telah memperoleh persetujuan dari Pihak Bank, untuk selanjutnya digunakan oleh seluruh pihak terkait sebagai dasar pelaksanaan dan pengawasan proyek.\n"
                          . "b. :klien merinci GBRKAB ke dalam Rencana Kerja dan Anggaran Biaya Bulanan/Triwulanan (RAB Bulanan/Triwulanan), yang digunakan sebagai tolok ukur pemantauan, evaluasi, serta penyesuaian rencana kerja dan anggaran pada periode berikutnya selama pelaksanaan proyek.\n"
                          . "c. Program operasional jangka pendek meliputi, antara lain, sub-sistem dari *Master Network Plan*, rencana pemanfaatan dana, serta rencana kebutuhan tenaga kerja, yang menjadi acuan awal dalam pelaksanaan dan pengawasan proyek.\n"
                          . "2. Tahapan Pelaksanaan\n"
                          . "d. Melakukan penelaahan dan verifikasi atas biaya-biaya pendahuluan (preliminary cost) yang telah dikeluarkan, baik yang bersumber dari *self financing* maupun dari fasilitas kredit Bank, berdasarkan dokumen pendukung yang tersedia, sebatas pemeriksaan kewajaran dan kelengkapan administratif.\n"
                          . "e. Melakukan penelaahan dokumen perizinan dan dokumen kerja sama yang berkaitan dengan pelaksanaan proyek, terbatas pada pemeriksaan kelengkapan dan kesesuaian administratif.\n"
                          . "f. Melakukan inspeksi atau peninjauan lapangan dalam rangka pemantauan pelaksanaan pembangunan dan pengadaan peralatan proyek, berdasarkan observasi visual dan data pendukung yang tersedia.\n"
                          . "g. Melakukan penelaahan terhadap perkembangan pelaksanaan pekerjaan, termasuk realisasi penanaman modal, pelaksanaan pembangunan infrastruktur, serta pengadaan peralatan, untuk dibandingkan dengan spesifikasi teknis dan rencana pembiayaan yang telah ditetapkan.\n"
                          . "h. Melakukan evaluasi kemajuan pekerjaan proyek dengan membandingkan capaian progres fisik dan realisasi biaya terhadap Rencana Kerja dan Anggaran Biaya (RAB) yang telah disetujui.\n"
                          . "i. Melakukan pemantauan realisasi penggunaan dana proyek, dengan membandingkan antara realisasi dan rencana anggaran sebagaimana tercantum dalam GBRKAB maupun RAB Bulanan/Triwulanan.\n"
                          . "j. Mengidentifikasi, menelaah, dan mengevaluasi potensi hambatan atau penyimpangan yang teramati selama pelaksanaan proyek, serta menyampaikan catatan dan pandangan umum terkait langkah-langkah penanganan yang dapat dipertimbangkan.\n"
                          . "k. Menyusun dan menyampaikan rekomendasi secara umum kepada Pihak Bank, berdasarkan hasil pemantauan dan evaluasi pelaksanaan proyek, terkait dengan tahapan kemajuan pekerjaan, baik dari aspek progres fisik, realisasi biaya, maupun faktor lain yang relevan dengan ruang lingkup pengawasan, tanpa bersifat instruktif atau mengikat.\n",
            ],
            'tor_pelaksanaan_pengawasan_2' => [
                'judul' => "pelaksanaan pengawasan",
                'teks'  => "Buku Laporan Pengawasan Proyek disusun dan disajikan secara sistematis sesuai dengan struktur laporan pengawasan, dengan pengelompokan bab sebagai berikut:",
            ],
            'tor_bab_pendahuluan' => [
                'judul' => "BAB I pendahuluan",
                'teks'  => "1. Gambaran umum perusahaan dan proyek\n"
                          . "2. Skema dan sumber pembiayaan proyek\n"
                          . "3. Maksud dan tujuan pelaksanaan pengawasan dan\n"
                          . "4. Ruang lingkup dan periode pelaporan\n",
            ],
            'tor_bab_metodologi_pengawasan' => [
                'judul' => "BAB II Metodologi Pengawasan",
                'teks'  => "1. Pendekatan pengawasan/monitoring yang digunakan\n"
                          . "2. Metode pengumpulan data (kunjungan lapangan dan telaah dokumen)\n"
                          . "3. Sumber data yang digunakan\n"
                          . "4. Asumsi dan batasan dalam pelaksanaan pengawasan\n",
            ],
            'tor_bab_gambaran_umum_proyek' => [
                'judul' => "BAB III Gambaran Umum Proyek",
                'teks'  => "1. Deskripsi umum proyek\n"
                          . "2. Lokasi dan karakteristik proyek\n"
                          . "3. Para pihak yang terlibat dalam pelaksanaan proyek\n"
                          . "4. Jadwal umum dan nilai proyek\n",
            ],
            'tor_bab_program_perencanaan_dan_pelaksanaan_baseline' => [
                'judul' => "BAB IV Program Perencanaan dan Pelaksanaan (Baseline Proyek)",
                'teks'  => "Bab ini memuat program perencanaan proyek yang telah disetujui sebagai acuan pengawasan, meliputi:\n"
                          . "1. Jadwal pelaksanaan proyek (*time schedule / reference time schedule*)\n"
                          . "2. Rencana pembiayaan dan anggaran biaya proyek\n"
                          . "3. Tahapan pelaksanaan pekerjaan\n"
                          . "4. Program manajemen proyek yang relevan\n",
            ],
            'tor_bab_perkembangan_pelaksanaan_proyek_fisik_dan_bi' => [
                'judul' => "BAB V Perkembangan Pelaksanaan Proyek (Fisik dan Biaya)",
                'teks'  => "1. Perkembangan Fisik Proyek\n"
                          . "Uraian yang dijelasan pada pembahasan ini meliputi:\n"
                          . "a. Prestasi kemajuan fisik proyek selama periode laporan berjalan (bulanan/triwulanan), dirinci menurut jenis pekerjaan\n"
                          . "b. Prestasi kemajuan fisik kumulatif sejak awal pelaksanaan proyek\n"
                          . "c. Perbandingan realisasi kemajuan fisik dengan jadwal rencana (*reference time schedule*)\n"
                          . "d. Deviasi atau penyimpangan kemajuan fisik, baik periode berjalan maupun kumulatif\n"
                          . "1. Perkembangan Biaya Proyek\n"
                          . "Uraian yang dijelasan pada pembahasan ini meliputi\n"
                          . "a. Perkembangan realisasi pembiayaan proyek, baik periode berjalan maupun kumulatif\n"
                          . "b. Perbandingan antara realisasi biaya dan rencana anggaran proyek\n"
                          . "c. Deviasi atau penyimpangan penggunaan biaya terhadap rencana anggaran\n"
                          . "d. Sumber pembiayaan proyek, baik dana sendiri maupun dana bank, termasuk rasio pembiayaan proyek (apabila relevan)\n"
                          . "1. Hambatan dan Penyimpangan Pelaksanaan\n"
                          . "Uraian yang dijelasan pada pembahasan ini meliputi:\n"
                          . "a. Hambatan, kendala, dan penyimpangan yang teridentifikasi selama pelaksanaan proyek di lapangan\n"
                          . "b. Faktor-faktor yang memengaruhi kemajuan fisik dan pembiayaan proyek.\n",
            ],
            'tor_bab_rencana_kerja_periode_berikutnya' => [
                'judul' => "BAB VI Rencana Kerja Periode Berikutnya",
                'teks'  => "Uraian yang dijelasan pada pembahasan ini meliputi:\n"
                          . "1. Rencana kegiatan dan target pekerjaan periode berikutnya\n"
                          . "2. Rencana penggunaan anggaran biaya untuk periode jangka pendek berikutnya\n"
                          . "3. Penyesuaian rencana kerja apabila diperlukan\n",
            ],
            'tor_bab_kesimpulan_saran' => [
                'judul' => "BAB VII Kesimpulan & saran",
                'teks'  => "1. Kesimpulan\n"
                          . "Kesimpulan hasil pengawasan atas kemajuan pelaksanaan proyek berdasarkan hasil kunjungan lapangan, penelaahan dokumen, dan data yang diperoleh selama periode pelaporan.\n"
                          . "1. Saran\n"
                          . "Saran dan catatan umum terkait hambatan atau penyimpangan yang teridentifikasi, sebagai bahan pertimbangan bagi Pemberi Tugas dan/atau Pihak Bank dalam rangka pemantauan pelaksanaan proyek.\n",
            ],
            'tor_hak_dan_kewajiban_para_pihak' => [
                'judul' => "Hak dan kewajiban para pihak",
                'teks'  => "Dengan ditandatanganinya proposal ini dan berlakunya proposal sebagai Surat Perintah Kerja (SPK), Para Pihak sepakat terhadap hak dan kewajiban sebagai berikut:",
            ],
            'tor_hak_dan_kewajiban_pemberi_tugas_pt_abc' => [
                'judul' => "Hak dan Kewajiban Pemberi Tugas PT ABC",
                'teks'  => "a. **:klien** berhak menerima laporan pengawasan secara berkala dan/atau laporan akhir sesuai dengan ruang lingkup dan jadwal penugasan.\n"
                          . "b. **:klien** berkewajiban memberikan dukungan, akses, dan bantuan yang diperlukan agar **KJPP Sugianto Prasodjo dan Rekan** dapat melaksanakan pekerjaan pengawasan secara efektif sesuai dengan ruang lingkup penugasan.\n"
                          . "c. **:klien** berkewajiban menyediakan dan menyerahkan data, dokumen, serta informasi yang relevan dan diperlukan untuk penyusunan laporan pengawasan secara tepat waktu dan lengkap.\n"
                          . "d. **:klien** berkewajiban membayarkan biaya jasa pengawasan kepada **KJPP Sugianto Prasodjo dan Rekan** sesuai dengan ketentuan biaya dan cara pembayaran yang disepakati dalam proposal/SPK ini.\n",
            ],
            'tor_hak_dan_kewajiban_konsultan_pengawas' => [
                'judul' => "Hak dan Kewajiban Konsultan Pengawas",
                'teks'  => "a. **Konsultan** berhak memperoleh data, dokumen, dan informasi yang diperlukan dari Pemberi Tugas dan/atau pihak terkait guna kelancaran pelaksanaan pekerjaan pengawasan.\n"
                          . "b. **Konsultan** berkewajiban melaksanakan pekerjaan pengawasan secara profesional, independen, dan objektif dengan mengerahkan keahlian serta sumber daya yang diperlukan sesuai dengan ruang lingkup penugasan.\n"
                          . "c. **Konsultan** berkewajiban menyusun dan menyampaikan laporan hasil pengawasan sebagai bahan informasi dan pertimbangan bagi Pihak Bank, dengan ketentuan bahwa laporan dan rekomendasi tersebut tidak bersifat mengikat dan sepenuhnya menjadi kewenangan Pihak Bank dalam pengambilan keputusan.\n"
                          . "d. Dalam hal diperlukan keahlian khusus yang tidak tersedia secara internal, **Konsultan** berhak melibatkan tenaga ahli atau konsultan lain dengan pengaturan kerja tertentu, yang tetap berada di bawah tanggung jawab **KJPP Sugianto Prasodjo dan Rekan**, serta dengan pemberitahuan kepada Pemberi Tugas dan Pihak Bank.\n"
                          . "e. **Konsultan berkewajiban**, atas permintaan Pihak Bank, untuk memberikan penjelasan dan klarifikasi terkait isi laporan pengawasan, termasuk dasar perhitungan, analisis, serta data pendukung yang digunakan dalam penyusunan laporan.\n"
                          . "f. **Konsultan berhak** menerima pembayaran biaya jasa pengawasan sesuai dengan ketentuan yang disepakati dalam proposal/SPK ini.\n"
                          . "g. **Konsultan berkewajiban** menunjuk seorang Penanggung Jawab Penugasan yang berwenang mewakili **KJPP Sugianto Prasodjo dan Rekan** selama masa penugasan dan dapat dihubungi oleh Pihak Bank sewaktu-waktu.\n",
            ],
            'tor_batas_wewenang_dan_tanggung_jawab' => [
                'judul' => "batas wewenang dan tanggung jawab",
                'teks'  => "Pekerjaan dilakukan secara terstruktur melalui tahapan:\n"
                          . "1. **KJPP Sugianto Prasodjo dan Rekan** melaksanakan penugasan dalam kapasitas sebagai konsultan pengawas/monitoring dan pemberi pandangan profesional, serta tidak memiliki kewenangan untuk mengambil keputusan teknis, operasional, maupun manajerial dalam pelaksanaan proyek.\n"
                          . "2. Seluruh surat-menyurat, laporan, rekomendasi, pandangan, dan usulan yang disusun oleh **KJPP Sugianto Prasodjo dan Rekan** sehubungan dengan pelaksanaan proyek disampaikan kepada Pihak Bank sebagai bahan informasi dan pertimbangan dalam rangka pelaksanaan fungsi pengawasan pembiayaan.\n"
                          . "3. Tanggung jawab pelaksanaan proyek, termasuk namun tidak terbatas pada perencanaan, pelaksanaan konstruksi, pengadaan, pengendalian mutu, keselamatan kerja, serta kepatuhan terhadap peraturan perundang-undangan, tetap berada sepenuhnya pada Pemberi Tugas, kontraktor, konsultan perencana, dan/atau pihak terkait lainnya sesuai dengan peran dan kewenangan masing-masing.\n"
                          . "4. Penugasan **KJPP Sugianto Prasodjo dan Rekan** tidak membebaskan konsultan lain, tenaga ahli, kontraktor, pemasok (*supplier*), maupun pihak terkait lainnya dari tanggung jawab, kewajiban, dan risiko yang melekat sesuai dengan perjanjian, ketentuan kontraktual, dan peraturan perundang-undangan yang berlaku.\n"
                          . "5. **KJPP Sugianto Prasodjo dan Rekan** tidak bertanggung jawab atas kegagalan proyek, keterlambatan pelaksanaan, perubahan desain, atau konsekuensi lain yang timbul akibat keputusan atau tindakan pihak lain di luar ruang lingkup penugasan pengawasan sebagaimana diatur dalam TOR dan proposal/SPK ini.\n"
                          . "Seluruh analisis dilakukan berdasarkan data yang diberikan oleh manajemen dan sumber lain yang dianggap wajar serta dapat dipercaya.\n",
            ],
            'tor_satuan_mata_uang' => [
                'judul' => "Satuan mata uang",
                'teks'  => "Seluruh nilai dan informasi keuangan yang disajikan dalam laporan pengawasan ini dinyatakan dalam satuan mata uang **Rupiah (Rp)**. Penggunaan satuan mata uang yang seragam dimaksudkan untuk menjaga konsistensi penyajian data serta memudahkan pemahaman atas informasi keuangan yang disampaikan. Apabila terdapat data atau informasi yang bersumber dari mata uang lain, maka data tersebut disajikan kembali dalam Rupiah (Rp) untuk kepentingan pelaporan, kecuali dinyatakan lain.",
            ],
            'tor_pelaksanaan_dan_jangka_waktu_pekerjaan' => [
                'judul' => "pelaksanaan DAN JANGKA WAKTU pekerjaan",
                'teks'  => "Pengawasan dilaksanakan sesuai dengan kebutuhan PIHAK BANK, dengan tetap memperhatikan ketentuan penugasan dan standar profesional yang berlaku pada KJPP Sugianto Prasodjo dan Rekan.\n"
                          . "\n"
                          . "Laporan pengawasan proyek akan kami serahkan dalam jangka waktu xx (-----) hari kerja terhitung setelah datadata yang lengkap terima dilakukan sebanyak 2 (dua) buku, dan disusun dalam Bahasa Indonesia. Adapun jadwal pelaksanaan pengerjaan penyusunan laporan pengawasan proyek sebagai berikut:\n",
            ],
            'tor_penutup' => [
                'judul' => "PENUTUP",
                'teks'  => "*Terms of Reference (TOR)* penugasan penyusunan Laporan Pengawasan ini dibuat sebagai acuan pelaksanaan pekerjaan dan merupakan satu kesatuan yang tidak terpisahkan dari Proposal. Dengan ditandatanganinya Proposal oleh Para Pihak dan berlakunya Surat Proposal tersebut sebagai Surat Perjanjian Kerja (SPK), TOR ini secara otomatis menjadi bagian yang mengikat dan berlaku bagi Para Pihak.",
            ],
        ],
        'lampiran2' => [
            'lampiran2_aspek_yuridis' => [
                'judul' => "ASPEK YURIDIS:",
                'teks'  => "1. Company Profile Perusahaan\n"
                          . "2. Akte Pendirian & Akte Perubahan/ Terakhir\n"
                          . "3. Copy Pengesahan Kemenkumham\n"
                          . "4. Copy NPWP, NIB, dan Surat Izin Usaha (IUP/IUP-K)\n"
                          . "5. Copy Sertifikat Tanah\n"
                          . "6. Copy IMB/PBG\n"
                          . "7. Surat izin dari Pemerintah Daerah / KRK terkait lokasi rencana proyek\n"
                          . "8. Copy Perjanjian lainnya yang berkaitan dengan rencana proyek\n",
            ],
            'lampiran2_aspek_teknis' => [
                'judul' => "ASPEK TEKNIS:",
                'teks'  => "1. Dokumen Perencanaan Tapak (Site Plan / Master Plan)\n"
                          . "2. Data Gambar Arsitektur, Sturktur dan MEP\n"
                          . "3. Data rencana anggaran biaya (RAB) harga pembelian atau harga perolehan\n"
                          . "4. Laporan Progress Pembangunan\n"
                          . "5. Kurva Pembangunan Proyek\n"
                          . "6. Perjanjian Kontrak dengan Kontraktor dan Supplier\n",
            ],
            'lampiran2_aspek_keuangan' => [
                'judul' => "ASPEK KEUANGAN",
                'teks'  => "1. Laporan Studi Kelayakan terdahulu (jika ada)\n"
                          . "2. Laporan keuangan Perusahaan selama 3 (tiga) tahun terakhir (*audited* / *unaudited*) *“sebagai informasi pendukung untuk memahami kondisi keuangan perusahaan dan konteks pembiayaan proyek.*\n"
                          . "3. Data realisasi pembayaran dan penggunaan dana proyek sampai dengan periode laporan berjalan\n"
                          . "4. ukti invoice dan bukti transfer bank terkait pembayaran pekerjaan proyek\n"
                          . "5. Salinan Perjanjian Persetujuan Kredit Investasi.\n",
            ],
        ],
    ],
];
