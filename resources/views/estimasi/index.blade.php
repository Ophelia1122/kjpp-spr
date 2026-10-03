@extends('layouts.app')

@section('title', 'Map Market')

@section('content')
{{-- Peta selalu tampil begitu halaman dibuka (2026-09-26, permintaan user).
     Klik pada peta menghitung ulang lewat fetch: hanya panel kiri yang
     ditukar, peta tetap pada posisi & perbesaran yang sama. Mengubah radius
     atau jenis properti tetap memuat ulang halaman lewat formulir. --}}
<div class="mx-auto max-w-7xl space-y-3 py-3 sm:space-y-4 sm:py-5">

    <x-page-header title="Map Market" class="py-3 pl-4 pr-3 sm:py-4 sm:pl-6 sm:pr-5"
        subtitle="<span class='hidden sm:inline'>{{ number_format($totalTitik, 0, ',', '.') }} titik data {{ $tahunData[0] }}&ndash;{{ $tahunData[1] }}</span>">
        <form method="GET" action="{{ route('estimasi.index') }}" class="grid w-full grid-cols-2 items-end gap-x-2 gap-y-1.5 sm:flex sm:w-auto sm:flex-wrap sm:gap-2">
            <div class="col-span-2 w-full sm:w-[190px]">
                <label for="koordinat" class="mb-0.5 block text-[10px] font-medium text-gray-500 dark:text-gray-400">Titik Koordinat</label>
                <input type="text" name="koordinat" id="koordinat"
                       value="{{ request('koordinat') }}" placeholder="-6.304484, 106.805611"
                       class="h-9 w-full rounded-md border-gray-300 text-xs shadow-sm dark:border-gray-600 dark:bg-gray-900">
            </div>

            <div class="w-full sm:w-[132px]">
                <label for="sumber" class="mb-0.5 block truncate text-center text-[10px] font-medium text-gray-500 dark:text-gray-400">Sumber data</label>
                <select name="sumber" id="sumber" class="h-9 w-full rounded-md border-gray-300 py-0 text-xs shadow-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Semua sumber</option>
                    @foreach (\App\Models\LandValuePoint::TIPE_LABELS as $kunci => $labelSumber)
                        <option value="{{ $kunci }}" @selected($sumber === $kunci)>{{ $labelSumber }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-[140px]">
                <label for="kelas" class="mb-0.5 block truncate text-center text-[10px] font-medium text-gray-500 dark:text-gray-400">Jenis properti</label>
                <select name="kelas" id="kelas" class="h-9 w-full rounded-md border-gray-300 py-0 text-xs shadow-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Models\LandValuePoint::KELAS_LABELS as $kunci => $labelKelas)
                        <option value="{{ $kunci }}" @selected($kelas === $kunci)>{{ $labelKelas }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-[140px]">
                <label for="tujuan" class="mb-0.5 block truncate text-center text-[10px] font-medium text-gray-500 dark:text-gray-400">Tujuan penilaian</label>
                <select name="tujuan" id="tujuan" class="h-9 w-full rounded-md border-gray-300 py-0 text-xs shadow-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Semua tujuan</option>
                    @foreach ($daftarTujuan as $t)
                        <option value="{{ $t }}" @selected($tujuan === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Radius diketik bebas; dikosongkan berarti 0,5 sampai 5 km bertahap. --}}
            <div class="w-full sm:w-[58px]">
                <label for="radius" class="mb-0.5 block truncate text-center text-[10px] font-medium text-gray-500 dark:text-gray-400">Radius</label>
                <input type="number" name="radius" id="radius" step="0.1" min="0.1" max="50"
                       value="{{ $radius }}" placeholder="3" data-catatan="Jari-jari pencarian pembanding, dalam kilometer. Kosongkan untuk memakai 3 km."
                       data-catatan-judul="Radius"
                       class="tanpa-pemutar h-9 w-full rounded-md border-gray-300 px-1 text-center text-xs shadow-sm dark:border-gray-600 dark:bg-gray-900">
            </div>

            {{-- Rentang tahun: satu garis, dua pegangan. Dua input range
                 ditumpuk; hanya pegangannya yang menerima klik. --}}
            <div class="col-span-2 w-full sm:w-[150px]">
                <label class="mb-0.5 block truncate text-center text-[10px] font-medium text-gray-500 dark:text-gray-400">
                    Tahun <span id="tahunTampil" class="font-semibold text-gray-700 dark:text-gray-300">{{ $tahunMin }}&ndash;{{ $tahunMax }}</span>
                </label>
                <div class="relative flex h-9 items-center">
                    <div class="absolute inset-x-0 h-1 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div id="tahunTerisi" class="absolute h-1 rounded bg-blue-600"></div>
                    <input type="range" name="tahun_min" id="tahun_min" aria-label="Tahun awal"
                           min="{{ $tahunData[0] }}" max="{{ $tahunData[1] }}" value="{{ $tahunMin }}"
                           class="geser-tahun pointer-events-none absolute inset-x-0 m-0 h-9 w-full appearance-none bg-transparent">
                    <input type="range" name="tahun_max" id="tahun_max" aria-label="Tahun akhir"
                           min="{{ $tahunData[0] }}" max="{{ $tahunData[1] }}" value="{{ $tahunMax }}"
                           class="geser-tahun pointer-events-none absolute inset-x-0 m-0 h-9 w-full appearance-none bg-transparent">
                </div>
            </div>

        </form>
    </x-page-header>

    <div class="grid grid-cols-1 items-start gap-2 lg:grid-cols-[360px_1fr]">

        {{-- Kolom kiri: panel hasil, ditukar tiap kali titik berganti. --}}
        {{-- Di ponsel peta muncul lebih dulu (2026-09-26, permintaan user). --}}
        {{-- Tingginya dipatok setinggi peta. Kartu pembanding memakai sisa
             ruangnya, jadi tabel di dalamnya digulir, bukan memanjangkan
             halaman (2026-09-27, permintaan user). --}}
        <div id="panelHasil" class="order-2 flex flex-col gap-2 lg:order-1 lg:h-[calc(100dvh-15rem)] lg:min-h-[420px]">
            @include('estimasi._hasil')
        </div>

        {{-- Kolom kanan: peta, tidak pernah dimuat ulang. --}}
        <div class="order-1 flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:order-2 lg:h-[calc(100dvh-15rem)] lg:min-h-[420px] dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2">
                <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Peta sebaran</h2>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-blue-600"></span> titik dicari</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-gray-400"></span> pembanding pasar</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 bg-gray-400"></span> objek penilaian</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span> di bawah tengah</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-rose-500"></span> di atas tengah</span>
                </div>
            </div>

            {{-- Tinggi peta dipatok (2026-09-26, feedback user): dulu ikut tinggi
                     kolom kiri, jadi berubah-ubah tiap titik baru dipilih. --}}
                {{-- flex-1 hanya di layar besar: di ponsel kartunya tanpa tinggi
                     tetap, sehingga flex-1 membuat peta setinggi 0. --}}
                <div id="petaWadah" class="relative h-[52dvh] max-h-[420px] min-h-[260px] border-t border-gray-100 lg:h-auto lg:max-h-none lg:min-h-0 lg:flex-1 dark:border-gray-700">
                <div id="petaEstimasi" class="absolute inset-0" style="background:#e5e7eb"></div>

                {{-- Penanda sedang memuat hasil baru sesudah klik peta. --}}
                <div id="petaSibuk" hidden
                     class="absolute left-1/2 top-3 z-[600] -translate-x-1/2 rounded-full bg-gray-900/80 px-3 py-1 text-[11px] font-medium text-white">
                    Menghitung&hellip;
                </div>

                {{-- Kotak rincian titik. Di layar lebar melayang di kiri bawah peta;
                     di ponsel dipindah ke bawah peta oleh skrip, supaya tidak
                     menutup peta dan tombol zoom (2026-10-03, feedback user).
                     Field pendek dua kolom, Lokasi/Sumber/Nomor selebar penuh
                     dengan huruf kecil, supaya kotak tidak menjulang. --}}
                <div id="petaIsi" hidden
                     class="border-t border-gray-100 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 lg:absolute lg:bottom-3 lg:left-3 lg:z-[600] lg:max-h-[min(440px,calc(100%-1.5rem))] lg:w-[290px] lg:overflow-y-auto lg:rounded-md lg:border-t-0 lg:bg-white/95 lg:shadow-lg lg:ring-1 lg:ring-black/5 dark:lg:bg-gray-800/95 dark:lg:ring-white/10">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Nilai tanah</p>
                        <button type="button" id="rincTutup" aria-label="Tutup rincian"
                                class="-mr-1 -mt-1 grid h-6 w-6 place-items-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">&times;</button>
                    </div>
                    <p id="rincNilai" class="text-lg font-bold leading-tight tabular-nums text-gray-900 dark:text-gray-100"></p>
                    <p id="rincBanding" class="text-[11px]"></p>

                    <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1.5 border-t border-gray-100 pt-2 text-xs dark:border-gray-700">
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-400">Jenis</dt><dd id="rincJenis" class="leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-400">Tanggal penilaian</dt><dd id="rincTgl" class="leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-400">Luas tanah / bangunan</dt><dd id="rincLuas" class="tabular-nums leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-400">Jarak dari titik dicari</dt><dd id="rincJarak" class="tabular-nums leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        {{-- Rincian nilai (2026-10-03, permintaan user). Baris yang
                             datanya kosong disembunyikan oleh skrip. --}}
                        <div id="rincTotalWrap"><dt id="rincTotalLabel" class="text-[10px] text-gray-500 dark:text-gray-400">Nilai penawaran total</dt><dd id="rincTotal" class="tabular-nums leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div id="rincTanahWrap"><dt class="text-[10px] text-gray-500 dark:text-gray-400">Nilai penawaran tanah</dt><dd id="rincTanah" class="tabular-nums leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div id="rincBgnWrap"><dt class="text-[10px] text-gray-500 dark:text-gray-400">Nilai penawaran bangunan</dt><dd id="rincBgn" class="tabular-nums leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div id="rincRateBgnWrap"><dt class="text-[10px] text-gray-500 dark:text-gray-400">Nilai per m² bangunan</dt><dd id="rincRateBgn" class="tabular-nums leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div class="col-span-2"><dt class="text-[10px] text-gray-500 dark:text-gray-400">Lokasi</dt><dd id="rincLokasi" class="text-[11px] leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div class="col-span-2"><dt class="text-[10px] text-gray-500 dark:text-gray-400">Sumber</dt><dd id="rincSumber" class="text-[11px] leading-snug text-gray-800 dark:text-gray-200"></dd></div>
                        <div class="col-span-2"><dt class="text-[10px] text-gray-500 dark:text-gray-400">Nomor laporan</dt><dd id="rincNomor" class="break-all text-[10px] leading-snug text-gray-600 dark:text-gray-300"></dd></div>
                    </dl>

                    <a id="rincMaps" href="#" target="_blank" rel="noopener"
                       class="mt-2 inline-block text-[11px] font-medium text-blue-600 hover:underline dark:text-blue-400">Buka di Google Maps &rarr;</a>
                </div>
            </div>

            {{-- Tempat kotak rincian di ponsel (di bawah peta, bukan menimpa). --}}
            <div id="petaIsiSlot" class="lg:hidden"></div>

            <p class="border-t border-gray-100 px-4 py-2 text-[11px] text-gray-500 dark:border-gray-700 dark:text-gray-400">
                Klik di mana saja pada peta untuk menghitung titik itu. Klik titik berwarna untuk melihat rinciannya.
            </p>
        </div>
    </div>

    {{-- Keterangan kaki dipendekkan jadi satu baris supaya kolom data tidak
         terdorong sampai halaman ikut bergulir (2026-09-27, permintaan user).
         Penjelasan panjangnya ada di halaman Cara Kerja. --}}
    <p class="text-[11px] text-gray-500 sm:truncate dark:text-gray-400">
        Dihitung dari {{ number_format($totalTitik, 0, ',', '.') }} titik data KJPP: objek penilaian dan pembanding
        pasar hasil survei. Titik tengahnya median berbobot, makin dekat dan makin baru makin besar
        pengaruhnya. Panduan internal, bukan pembanding laporan.
        <a href="{{ route('estimasi.cara') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">Cara kerja &amp; asal data &rarr;</a>
    </p>
</div>

<style>
    /* Pegangan penggeser tahun: hanya pegangannya yang bisa ditarik,
       garisnya digambar oleh div di belakangnya. */
    /* Titik objek penilaian digambar kotak; pembanding tetap bulat. */
    .titik-aset { rx: 0; ry: 0; }

    /* Matikan scroll anchoring (2026-10-03, laporan user): di ponsel kotak
       detail muncul DI ATAS daftar pembanding, dan peramban menjaga daftar
       itu tetap di layar dengan menggeser halaman sebesar tinggi kotak —
       satu ketukan titik melempar layar ke "Pembanding terdekat". */
    html { overflow-anchor: none; }

    /* Baris pembanding yang sedang dibuka detailnya di peta. */
    tr.baris-terpilih { background-color: #dbeafe; }
    .dark tr.baris-terpilih { background-color: #1e3a5f; }

    /* Kolom radius dipersempit, jadi tombol naik-turun bawaan dibuang
       supaya angkanya tetap terbaca (2026-09-27, permintaan user). */
    .tanpa-pemutar::-webkit-outer-spin-button,
    .tanpa-pemutar::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .tanpa-pemutar { -moz-appearance: textfield; appearance: textfield; }

    /* Lencana catatan pada kartu tren: berkedip pelan, dan kotak catatannya
       muncul saat kursor mengambang atau lencananya menerima fokus papan
       ketik (2026-09-27, permintaan user). */
    .tanda-catatan { display: inline-flex; animation: kedip 1.6s ease-in-out infinite; }
    .tanda-catatan:hover, .tanda-catatan:focus-visible { animation: none; }
    .kotak-catatan { display: none; }
    .kotak-catatan.terlihat { display: block; }

    @keyframes kedip { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }

    @media (prefers-reduced-motion: reduce) {
        .tanda-catatan { animation: none; }
    }

    .geser-tahun::-webkit-slider-thumb {
        -webkit-appearance: none; pointer-events: auto; cursor: grab;
        height: 16px; width: 16px; border-radius: 9999px;
        background: #2563eb; border: 2px solid #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .3);
    }
    .geser-tahun::-moz-range-thumb {
        pointer-events: auto; cursor: grab;
        height: 16px; width: 16px; border: 2px solid #fff; border-radius: 9999px; background: #2563eb;
    }
    .geser-tahun::-webkit-slider-runnable-track,
    .geser-tahun::-moz-range-track { background: transparent; }
</style>
<link rel="stylesheet" href="{{ asset('js/leaflet/leaflet.css') }}">
<script src="{{ asset('js/leaflet/leaflet.js') }}"></script>
<script>
(function () {
    var wadah = document.getElementById('petaEstimasi');
    if (!wadah || typeof L === 'undefined') return;

    var panel    = document.getElementById('panelHasil');
    var sibuk    = document.getElementById('petaSibuk');
    var isi      = document.getElementById('petaIsi');
    // URL relatif, bukan absolut: NAS sering dibuka lewat alamat yang berbeda
    // dari APP_URL (mis. IP Tailscale), dan URL beda asal membuat fetch serta
    // history.replaceState gagal.
    var urlDasar = @json(route('estimasi.index', [], false));

    // Tampilan awal tanpa koordinat: Jabodetabek, tempat sebagian besar data.
    var peta = L.map(wadah, { scrollWheelZoom: true, zoomControl: true })
        .setView([-6.25, 106.82], 10);

    var jalan = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    });
    var satelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, attribution: 'Citra &copy; Esri'
    });
    var label = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19
    });

    jalan.addTo(peta);
    L.control.layers({ 'Peta': jalan, 'Satelit': L.layerGroup([satelit, label]) },
        null, { position: 'topright' }).addTo(peta);

    var lapisan  = L.layerGroup().addTo(peta);   // titik + lingkaran radius
    var daftar   = [];                           // {data, penanda, buka}
    var tengah   = 0;
    var terpilih = null;

    var rupiah = function (n) { return 'Rp' + n.toLocaleString('id-ID'); };
    var jarakTeks = function (km) { return km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(2).replace('.', ',') + ' km'; };

    document.getElementById('rincTutup').addEventListener('click', tutupRincian);

    // ---- penggeser rentang tahun ----
    var thMin  = document.getElementById('tahun_min');
    var thMax  = document.getElementById('tahun_max');
    var thTeks = document.getElementById('tahunTampil');
    var thIsi  = document.getElementById('tahunTerisi');

    function tahunAwal() { return Math.min(+thMin.value, +thMax.value); }
    function tahunAkhir() { return Math.max(+thMin.value, +thMax.value); }

    function tulisTahun() {
        thTeks.textContent = tahunAwal() + '–' + tahunAkhir();

        // Bagian garis yang terpilih.
        var min = +thMin.min, max = +thMin.max, rentang = Math.max(1, max - min);
        thIsi.style.left  = ((tahunAwal() - min) / rentang * 100) + '%';
        thIsi.style.width = ((tahunAkhir() - tahunAwal()) / rentang * 100) + '%';
    }

    // Peta di ponsel kadang tidak menggambar ubin karena tingginya belum
    // final saat peta dibuat (2026-09-26, feedback user). Diukur ulang tiap
    // kali kotaknya berubah ukuran, bukan sekali lewat timer.
    function ukurUlang() { peta.invalidateSize(); }

    if (window.ResizeObserver) {
        new ResizeObserver(ukurUlang).observe(wadah);
    }
    window.addEventListener('load', ukurUlang);
    window.addEventListener('orientationchange', function () { setTimeout(ukurUlang, 300); });
    setTimeout(ukurUlang, 300);

    // Di ponsel kotak rincian ditaruh DI BAWAH peta, bukan di atasnya: dulu
    // menutup peta dan tombol zoom (2026-10-03, feedback user).
    var ponsel   = window.matchMedia('(max-width: 1023px)');
    var wadahPeta = document.getElementById('petaWadah');
    var slotIsi   = document.getElementById('petaIsiSlot');

    function tempatkanRincian() {
        (ponsel.matches ? slotIsi : wadahPeta).appendChild(isi);
    }

    tempatkanRincian();
    if (ponsel.addEventListener) ponsel.addEventListener('change', tempatkanRincian);
    else if (ponsel.addListener) ponsel.addListener(tempatkanRincian);

    function tutupRincian() {
        isi.hidden = true;
        if (terpilih) {
            terpilih.setStyle({ weight: 1, color: terpilih.options.warnaAsli });
            terpilih = null;
        }
        panel.querySelectorAll('tr[data-titik].baris-terpilih').forEach(function (tr) {
            tr.classList.remove('baris-terpilih');
        });
    }

    function tulis(t, penanda) {
        isi.hidden = false;

        // Kotak ada di bawah peta pada ponsel. Gulir hanya sebanyak yang perlu
        // supaya judul dan nilainya (±150 px teratas) terlihat; sisanya bisa
        // digulir sendiri. Menampilkan seluruh kotak mendorong peta keluar layar.
        if (ponsel.matches) {
            var kurang = isi.getBoundingClientRect().top + 150 - window.innerHeight;
            if (kurang > 0) window.scrollBy({ top: kurang, behavior: 'smooth' });
        }

        document.getElementById('rincNilai').textContent = rupiah(t.rp) + ' /m²';

        var selisih = tengah ? Math.round((t.rp - tengah) / tengah * 100) : 0;
        var banding = document.getElementById('rincBanding');
        banding.textContent = selisih === 0
            ? 'sama dengan titik tengah'
            : (selisih > 0 ? '+' : '') + selisih + '% terhadap titik tengah';
        banding.className = 'text-[11px] ' + (selisih > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400');

        document.getElementById('rincJenis').textContent  = t.jn || '—';
        document.getElementById('rincTgl').textContent    = t.tgl || ('Tahun ' + t.th);
        document.getElementById('rincLuas').textContent   =
            (t.lt ? t.lt.toLocaleString('id-ID') + ' m²' : '—') + ' / ' + (t.lb ? t.lb.toLocaleString('id-ID') + ' m²' : '—');
        // Rincian nilai: baris tanpa data disembunyikan. Objek penilaian KJPP
        // tidak punya "penawaran", jadi totalnya disebut nilai pasar.
        function isiNilai(id, nilai, satuan) {
            var dd = document.getElementById(id);
            dd.parentElement.hidden = ! nilai;
            dd.textContent = nilai ? rupiah(nilai) + (satuan || '') : '';
        }
        document.getElementById('rincTotalLabel').textContent = t.tipe === 'aset' ? 'Nilai pasar total' : 'Nilai penawaran total';
        isiNilai('rincTotal',   t.pt);
        isiNilai('rincTanah',   t.pl);
        isiNilai('rincBgn',     t.pb);
        isiNilai('rincRateBgn', t.rb, ' /m²');
        document.getElementById('rincJarak').textContent  = jarakTeks(t.km);
        document.getElementById('rincLokasi').textContent = t.almt || [t.lk, t.kota].filter(Boolean).join(', ') || '—';
        document.getElementById('rincSumber').textContent = t.tipe === 'aset'
            ? 'Objek penilaian KJPP'
            : ['Data pembanding', t.jenisTransaksi, t.namaSumber, t.statusSumber, t.telepon]
                .filter(Boolean).join(' · ');
        document.getElementById('rincNomor').textContent  = t.no || '—';
        document.getElementById('rincMaps').href          = 'https://www.google.com/maps?q=' + t.la + ',' + t.lo;

        if (terpilih) terpilih.setStyle({ weight: 1, color: terpilih.options.warnaAsli });
        penanda.setStyle({ weight: 4, color: '#111827' });
        terpilih = penanda;

        tandaiBaris(daftar.findIndex(function (x) { return x.data === t; }));
    }

    /** Sorot baris tabel yang sama dengan titik terpilih, dan bawa ke layar. */
    function tandaiBaris(indeks) {
        panel.querySelectorAll('tr[data-titik].baris-terpilih').forEach(function (tr) {
            tr.classList.remove('baris-terpilih');
        });

        var tr = indeks >= 0 ? panel.querySelector('tr[data-titik="' + indeks + '"]') : null;

        if (tr) {
            tr.classList.add('baris-terpilih');

            // Gulir HANYA di dalam kotak tabel. scrollIntoView() menggulir
            // seluruh halaman, sehingga di ponsel (tabel di bawah peta) satu
            // ketukan pada titik melempar layar ke daftar pembanding
            // (2026-10-03, laporan user).
            var kotak = tr.closest('.overflow-y-auto');

            if (kotak) {
                // Baris dibawa ke TENGAH bagian tabel yang terlihat (di bawah
                // kepala lengket), dengan gerak halus, supaya jelas baris mana
                // yang dimaksud dan baris di atas-bawahnya ikut terlihat
                // (2026-10-03, laporan user).
                var kepala = kotak.querySelector('thead');
                var tinggiKepala = kepala ? kepala.offsetHeight : 0;
                var rKotak = kotak.getBoundingClientRect();
                var rBaris = tr.getBoundingClientRect();
                var tengahLayar = tinggiKepala + (rKotak.height - tinggiKepala) / 2;
                var geser = (rBaris.top - rKotak.top) + rBaris.height / 2 - tengahLayar;

                kotak.scrollTo({ top: Math.max(0, kotak.scrollTop + geser), behavior: 'smooth' });
            }
        }
    }

    /** Klik baris tabel: buka detail titik dan arahkan peta ke lokasinya. */
    function bukaDariTabel(tr) {
        var titik = daftar[+tr.getAttribute('data-titik')];

        if (! titik) return;   // baris di luar 200 titik yang digambar

        titik.buka();
        peta.flyTo([titik.data.la, titik.data.lo], Math.max(peta.getZoom(), 16), { duration: 0.6 });

        // Di ponsel peta ada di atas tabel; bawa ke layar supaya terlihat.
        if (window.matchMedia('(max-width: 1023px)').matches) {
            wadah.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    panel.addEventListener('click', function (e) {
        if (e.target.closest('a, button')) return;   // tautan Google Maps & Excel tetap jalan
        var tr = e.target.closest('tr[data-titik]');
        if (tr) bukaDariTabel(tr);
    });

    panel.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('tr[data-titik]')) {
            e.preventDefault();
            bukaDariTabel(e.target);
        }
    });

    /** Gambar ulang titik dari data panel. */
    function gambar(pindahkanTampilan) {
        var simpul = document.getElementById('dataPeta');
        if (!simpul) return;

        var data = JSON.parse(simpul.textContent || '{}');

        lapisan.clearLayers();
        daftar = [];
        tengah = data.tengah || 0;
        tutupRincian();

        var batas = [];

        (data.titik || []).forEach(function (t) {
            var warna = t.rp > tengah ? '#e11d48' : '#10b981';
            // Bentuk membedakan asal data (2026-09-27, permintaan user):
            // bulat = pembanding pasar, kotak = objek penilaian KJPP.
            var penanda = L.circleMarker([t.la, t.lo], {
                radius: 7, color: warna, fillColor: warna, fillOpacity: 0.8, weight: 1,
                warnaAsli: warna,
                className: t.tipe === 'aset' ? 'titik-aset' : ''
            }).addTo(lapisan);

            function buka(e) {
                if (e) L.DomEvent.stopPropagation(e);
                tulis(t, penanda);
            }

            penanda.on('click', buka);
            penanda.bindTooltip(rupiah(t.rp), { direction: 'top' });

            // Bidang klik lebih lebar: titik 14 px terlalu kecil untuk kursor.
            L.circleMarker([t.la, t.lo], {
                radius: 18, opacity: 0, fillOpacity: 0.01, fillColor: warna, weight: 0, interactive: true
            }).addTo(lapisan).on('click', buka);

            daftar.push({ data: t, penanda: penanda, buka: buka });
            batas.push([t.la, t.lo]);
        });

        if (data.pusat) {
            L.circleMarker([data.pusat[0], data.pusat[1]], {
                radius: 9, color: '#1d4ed8', fillColor: '#2563eb', fillOpacity: 1, weight: 2
            }).addTo(lapisan).bindTooltip('Titik yang dicari', { direction: 'top' });

            batas.push([data.pusat[0], data.pusat[1]]);

            if (data.radius > 0) {
                L.circle([data.pusat[0], data.pusat[1]], {
                    radius: data.radius * 1000,
                    color: '#2563eb', weight: 1, fillColor: '#3b82f6', fillOpacity: 0.06
                }).addTo(lapisan);
            }
        }

        if (pindahkanTampilan && batas.length) {
            peta.fitBounds(L.latLngBounds(batas).pad(0.15), { maxZoom: 17 });
        }
    }

    /**
     * Terbangkan peta ke koordinat (2026-10-03, masukan tim): koordinat yang
     * baru diketik/ditempel harus langsung terlihat, bukan menunggu pengguna
     * zoom out lalu menggeser sendiri. Zoom mengikuti radius yang terpakai,
     * sehingga seluruh lingkaran pencarian muat di layar.
     */
    function terbangKe(lat, lon) {
        var simpul = document.getElementById('dataPeta');
        var data   = simpul ? JSON.parse(simpul.textContent || '{}') : {};

        if (data.radius > 0) {
            // Batas dihitung manual: L.circle yang belum dipasang di peta tidak
            // bisa memberi getBounds() (galat itu dulu memicu muat ulang halaman).
            var dLat = data.radius / 111.32;
            var dLon = data.radius / (111.32 * Math.cos(lat * Math.PI / 180));
            var kotak = L.latLngBounds([lat - dLat, lon - dLon], [lat + dLat, lon + dLon]);
            peta.flyToBounds(kotak.pad(0.12), { maxZoom: 17, duration: 0.8 });
        } else {
            peta.flyTo([lat, lon], 16, { duration: 0.8 });
        }
    }

    // Hitung ulang satu koordinat tanpa memuat ulang halaman.
    var permintaan = 0;

    // terbang = true bila koordinat datang dari kotak input (diketik, ditempel,
    // Enter). Klik di peta dan ganti filter tidak menggeser tampilan.
    function hitung(lat, lon, terbang) {
        var nomor = ++permintaan;
        var koordinat = lat.toFixed(6) + ', ' + lon.toFixed(6);

        titikAktif = [lat, lon];

        var parameter = new URLSearchParams();
        parameter.set('koordinat', koordinat);

        var kelas  = document.getElementById('kelas').value;
        var tujuan = document.getElementById('tujuan').value;
        var radius   = document.getElementById('radius').value;
        var sumber = document.getElementById('sumber').value;
        if (sumber)   parameter.set('sumber', sumber);
        if (kelas)  parameter.set('kelas', kelas);
        if (tujuan) parameter.set('tujuan', tujuan);
        if (radius)   parameter.set('radius', radius);
        parameter.set('tahun_min', Math.min(+thMin.value, +thMax.value));
        parameter.set('tahun_max', Math.max(+thMin.value, +thMax.value));

        document.getElementById('koordinat').value = koordinat;
        sibuk.hidden = false;

        fetch(urlDasar + '?' + parameter.toString() + '&partial=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.ok ? res.text() : Promise.reject(res.status); })
            .then(function (html) {
                if (nomor !== permintaan) return;        // jawaban lama, abaikan
                panel.innerHTML = html;
                gambar(false);
                if (terbang) terbangKe(lat, lon);

                // Alamat ikut diperbarui supaya bisa disalin/di-muat ulang.
                try {
                    history.replaceState(null, '', urlDasar + '?' + parameter.toString());
                } catch (e) {
                    // Peramban menolak (mis. beda asal) — hasilnya tetap tampil.
                }
            })
            .catch(function () {
                if (nomor === permintaan) window.location.href = urlDasar + '?' + parameter.toString();
            })
            .finally(function () { if (nomor === permintaan) sibuk.hidden = true; });
    }

    // ---- semuanya berubah otomatis, tanpa tombol (2026-09-26, permintaan user) ----
    var titikAktif = null;
    var isiKoordinat = document.getElementById('koordinat');
    var jedaKetik = null;

    var dataAwal = document.getElementById('dataPeta');
    if (dataAwal) {
        var awal = JSON.parse(dataAwal.textContent || '{}');
        if (awal.pusat) titikAktif = awal.pusat;
    }

    /** Baca "-6.3, 106.8" dari kotak koordinat. */
    function bacaKoordinat() {
        var cocok = (isiKoordinat.value || '').match(/(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)/);
        if (!cocok) return null;

        var la = parseFloat(cocok[1]), lo = parseFloat(cocok[2]);
        return (la >= -90 && la <= 90 && lo >= -180 && lo <= 180) ? [la, lo] : null;
    }

    function hitungUlang(terbang) {
        var t = bacaKoordinat() || titikAktif;
        if (t) hitung(t[0], t[1], terbang === true);
    }

    // Koordinat diketik/ditempel: tunggu berhenti mengetik sebentar.
    isiKoordinat.addEventListener('input', function () {
        clearTimeout(jedaKetik);
        jedaKetik = setTimeout(function () {
            var t = bacaKoordinat();
            if (t) hitung(t[0], t[1], true);
        }, 500);
    });

    // Jenis properti & radius & tahun: langsung hitung ulang.
    document.getElementById('kelas').addEventListener('change', hitungUlang);
    document.getElementById('tujuan').addEventListener('change', hitungUlang);
    document.getElementById('sumber').addEventListener('change', hitungUlang);
    document.getElementById('radius').addEventListener('input', function () {
        clearTimeout(jedaKetik);
        jedaKetik = setTimeout(hitungUlang, 500);
    });

    [thMin, thMax].forEach(function (g) {
        g.addEventListener('input', tulisTahun);
        g.addEventListener('change', hitungUlang);
    });

    // Enter di kotak koordinat tidak perlu memuat ulang halaman.
    document.querySelector('form').addEventListener('submit', function (e) {
        e.preventDefault();
        hitungUlang(true);
    });

    tulisTahun();

    // Klik peta: dekat titik = buka rinciannya, selain itu hitung titik baru.
    peta.on('click', function (e) {
        var layar = peta.latLngToContainerPoint(e.latlng), dekat = null, jarakPx = 1e9;

        daftar.forEach(function (x) {
            var p = peta.latLngToContainerPoint(x.penanda.getLatLng());
            var d = Math.hypot(p.x - layar.x, p.y - layar.y);
            if (d < jarakPx) { jarakPx = d; dekat = x; }
        });

        if (dekat && jarakPx <= 32) {
            dekat.buka();
            return;
        }

        hitung(e.latlng.lat, e.latlng.lng);
    });

    gambar(true);

    // Kotak catatan pada kartu tren. Pendengarnya dipasang di dokumen, sebab
    // panel hasil ditukar tiap kali titik baru dipilih.
    function catatan(sasaran, tampil) {
        var lencana = sasaran.closest ? sasaran.closest('.tanda-catatan') : null;

        if (! lencana) {
            return;
        }

        var kotak = lencana.closest('.kartu-tren').querySelector('.kotak-catatan');

        if (kotak) {
            kotak.classList.toggle('terlihat', tampil);
        }
    }

    document.addEventListener('mouseover', function (e) { catatan(e.target, true); });
    document.addEventListener('mouseout',  function (e) { catatan(e.target, false); });
    document.addEventListener('focusin',   function (e) { catatan(e.target, true); });
    document.addEventListener('focusout',  function (e) { catatan(e.target, false); });

    // Layar sentuh tidak punya kursor mengambang: ketuk untuk membuka.
    document.addEventListener('click', function (e) {
        var lencana = e.target.closest('.tanda-catatan');

        if (! lencana) {
            return;
        }

        var kotak = lencana.closest('.kartu-tren').querySelector('.kotak-catatan');

        if (kotak) {
            kotak.classList.toggle('terlihat');
        }
    });
})();
</script>
@endsection
