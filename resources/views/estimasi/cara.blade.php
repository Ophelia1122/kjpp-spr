@extends('layouts.app')

@section('title', 'Cara Kerja Map Market')

@section('content')
{{-- Halaman penjelasan (2026-09-27, permintaan user): menjawab "angka ini
     dari mana" untuk penilai sendiri maupun bila ada audit. --}}
<div class="mx-auto max-w-4xl space-y-4 py-5">

    <x-page-header title="Cara Kerja Map Market"
        subtitle="Asal data, rumus, hasil uji, dan batasannya">
        <a href="{{ route('estimasi.index') }}"
           class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            &larr; Kembali ke peta
        </a>
    </x-page-header>

    @php
        $kartu = 'rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800';
        $judul = 'text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
        $rp = fn ($v) => 'Rp' . number_format($v, 0, ',', '.');
    @endphp

    {{-- ---------- 1. Asal data ---------- --}}
    <div class="{{ $kartu }}">
        <h2 class="{{ $judul }}">1. Asal data</h2>

        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Seluruh angka berasal dari berkas resmi kantor, bukan dari sumber luar. Ada dua jenis, dan
            keduanya tidak pernah dicampur diam-diam; jenisnya bisa dipilih di saringan
            <span class="font-medium">Sumber data</span>, dan bentuknya berbeda di peta.
        </p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Jenis</th>
                        <th class="px-3 py-2">Titik</th>
                        <th class="px-3 py-2">Isi</th>
                        <th class="px-3 py-2">Bentuk di peta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">Data pembanding</td>
                        <td class="px-3 py-2 tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($jumlah['pembanding'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Penawaran &amp; transaksi pasar hasil survei, lengkap dengan nama dan nomor telepon sumbernya</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">bulat</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">Objek penilaian</td>
                        <td class="px-3 py-2 tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($jumlah['aset'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Kesimpulan nilai dari laporan KJPP sendiri</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">kotak</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">
            Tahun data {{ $tahunData[0] }}&ndash;{{ $tahunData[1] }}.
            Bawaannya <span class="font-medium">data pembanding</span>, karena itu yang mencerminkan pasar.
            Kesimpulan penilaian cenderung lebih konservatif, jadi mencampur keduanya membuat titik tengah
            bergantung pada komposisi yang kebetulan ada di lokasi itu, sulit dijelaskan dan tidak
            sebanding antar lokasi.
        </p>
    </div>

    {{-- ---------- 2. Penyaringan saat impor ---------- --}}
    <div class="{{ $kartu }}">
        <h2 class="{{ $judul }}">2. Yang ditolak saat impor</h2>
        <ul class="mt-2 space-y-1.5 text-sm text-gray-700 dark:text-gray-300">
            <li>&bull; Koordinat kosong atau di luar wilayah Indonesia.</li>
            <li>&bull; Baris tanpa harga per m&sup2;, baik tanah maupun bangunan.</li>
            <li>&bull; Harga di bawah {{ $rp(50000) }} atau di atas {{ $rp(200000000) }} per m&sup2;; hampir pasti salah ketik.</li>
            <li>&bull; Berkas revisi ganda (mis. &ldquo;2019.R1&rdquo;) yang isinya sama persis dengan berkas aslinya.</li>
        </ul>
        <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">
            Koordinat di berkas tersimpan dengan koma ribuan; <code>-6,339,724</code> berarti
            <code>-6.339724</code>. Titik desimalnya dicari otomatis sampai angkanya masuk rentang Indonesia.
        </p>
    </div>

    {{-- ---------- 3. Rumus ---------- --}}
    <div class="{{ $kartu }}">
        <h2 class="{{ $judul }}">3. Rumus</h2>

        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Langkah 1: bobot tiap pembanding</p>
        <pre class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">w = 1/(0,2 + jarak_km) &times; 0,88^(tahun_ini &minus; tahun_data)</pre>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
            Makin dekat, makin berat. Bobot menyusut 12% tiap tahun usia data. Angka 0,2 mencegah pembagian
            nol untuk titik yang tepat di lokasi.
        </p>

        <p class="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">Langkah 2: titik tengah</p>
        <pre class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">M = nilai ke-k, dengan k terkecil yang memenuhi
    &Sigma;(w&#8321;..w&#8342;) &ge; 0,5 &times; &Sigma;(semua w)</pre>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
            Median berbobot, bukan rata-rata, supaya satu pencilan mahal tidak menarik hasilnya.
        </p>

        <p class="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">Langkah 3: rentang</p>
        <pre class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">batas bawah = M &divide; faktor
batas atas  = M &times; faktor</pre>
    </div>

    {{-- ---------- 4. Kalibrasi ---------- --}}
    <div class="{{ $kartu }}">
        <h2 class="{{ $judul }}">4. Dari mana faktornya</h2>

        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Bukan angka tebakan. Seluruh data diuji ulang: tiap titik disembunyikan nilainya, diestimasi
            memakai titik lain, lalu sebaran galatnya diukur. Hasil uji atas 1.200 titik pembanding:
        </p>

        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Radius</th>
                        <th class="px-3 py-2">Median galat</th>
                        <th class="px-3 py-2">Faktor dipakai</th>
                        <th class="px-3 py-2">Nilai asli masuk rentang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 tabular-nums dark:divide-gray-700">
                    <tr><td class="px-3 py-2">sampai 1 km</td><td class="px-3 py-2">18%</td><td class="px-3 py-2 font-semibold">1,75</td><td class="px-3 py-2">80%</td></tr>
                    <tr><td class="px-3 py-2">sampai 2 km</td><td class="px-3 py-2">21%</td><td class="px-3 py-2 font-semibold">1,85</td><td class="px-3 py-2">80%</td></tr>
                    <tr><td class="px-3 py-2">di atas 2 km</td><td class="px-3 py-2">24%</td><td class="px-3 py-2 font-semibold">1,90</td><td class="px-3 py-2">80%</td></tr>
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">
            Median rasio nilai asli dibagi estimasi = 1,00x, artinya estimasinya tidak condong ke atas
            maupun ke bawah. Faktor dipilih supaya menutup sekitar 80% kasus: lebih sempit terlalu sering
            meleset, lebih lebar jadi tidak berguna.
        </p>
    </div>

    {{-- ---------- 5. Jenis properti & satuan ---------- --}}
    <div class="{{ $kartu }}">
        <h2 class="{{ $judul }}">5. Jenis properti dan satuannya</h2>
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Kategori</th>
                        <th class="px-3 py-2">Satuan</th>
                        <th class="px-3 py-2">Isinya</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">Tanah &amp; Bangunan</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">per m&sup2; tanah</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Rumah tinggal, tanah kosong, sawah, tambak, kebun, gudang, pabrik, toko, gedung, kampus, villa, showroom, kantor tanah &amp; bangunan</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">Ruko</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">per m&sup2; bangunan</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Ruko / rukan</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">Apart / OS / Kios</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">per m&sup2; bangunan</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Unit apartemen, office space, kios, satuan rumah susun</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">
            Dua satuan itu tidak pernah dijumlahkan. Kalau saringan jenis dibuka ke &ldquo;semua jenis&rdquo;
            dan pembandingnya memakai dua satuan sekaligus, kartu hasilnya memberi peringatan;
            angkanya tidak bisa dibaca langsung.
        </p>
    </div>

    {{-- ---------- 6. Batasan ---------- --}}
    <div class="{{ $kartu }}">
        <h2 class="{{ $judul }}">6. Batasan yang harus diketahui</h2>
        <ul class="mt-2 space-y-1.5 text-sm text-gray-700 dark:text-gray-300">
            <li>&bull; Sebagian besar isinya <span class="font-medium">penawaran</span>, bukan harga transaksi. Penawaran biasanya di atas harga jadi.</li>
            <li>&bull; Tujuan penilaian hanya diketahui untuk sebagian data pembanding, karena tidak semua laporan induknya ada di berkas objek penilaian. Yang tidak diketahui tetap ikut terhitung pada pilihan &ldquo;semua tujuan&rdquo;.</li>
            <li>&bull; Tanah pertanian dan tanah matang berada dalam satu kategori. Di pinggiran kota keduanya bisa berjarak dekat dengan harga jauh berbeda; jenis objeknya bisa dilihat di kotak rincian tiap titik.</li>
            <li>&bull; Estimasi ini <span class="font-medium">alat bantu, bukan penilaian</span>. Tidak boleh dikutip sebagai pembanding di laporan; pembanding laporan harus hasil survei sendiri dengan sumber yang bisa dikonfirmasi.</li>
        </ul>
    </div>
</div>
@endsection
