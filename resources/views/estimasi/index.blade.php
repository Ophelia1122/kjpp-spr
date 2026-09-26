@extends('layouts.app')

@section('title', 'Estimasi Nilai Tanah')

@section('content')
{{-- Peta selalu tampil begitu halaman dibuka (2026-09-26, permintaan user).
     Klik pada peta menghitung ulang lewat fetch: hanya panel kiri yang
     ditukar, peta tetap pada posisi & perbesaran yang sama. Mengubah radius
     atau jenis properti tetap memuat ulang halaman lewat formulir. --}}
<div class="mx-auto max-w-7xl space-y-4 py-5">

    <x-page-header title="Estimasi Nilai Tanah"
        subtitle="Rentang indikatif nilai tanah per m&sup2; dari titik koordinat &middot; {{ number_format($totalTitik, 0, ',', '.') }} titik data {{ $tahunData[0] }}&ndash;{{ $tahunData[1] }}">
        <form method="GET" action="{{ route('estimasi.index') }}" class="flex flex-wrap items-end gap-2">
            <div class="w-[230px]">
                <label for="koordinat" class="mb-0.5 block text-[11px] font-medium text-gray-500 dark:text-gray-500">Koordinat (dari Google Maps)</label>
                <input type="text" name="koordinat" id="koordinat"
                       value="{{ request('koordinat') }}" placeholder="-6.304484, 106.805611"
                       class="h-9 w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
            </div>

            <div class="w-[160px]">
                <label for="kelompok" class="mb-0.5 block text-[11px] font-medium text-gray-500 dark:text-gray-500">Jenis properti</label>
                <select name="kelompok" id="kelompok" class="h-9 w-full rounded-md border-gray-300 py-0 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Models\LandValuePoint::GROUPS as $kunci => $label)
                        <option value="{{ $kunci }}" @selected($kelompok === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Radius diketik bebas; dikosongkan berarti 0,5 sampai 5 km bertahap. --}}
            <div class="w-[104px]">
                <label for="radius" class="mb-0.5 block text-[11px] font-medium text-gray-500 dark:text-gray-500">Radius (km)</label>
                <input type="number" name="radius" id="radius" step="0.1" min="0.1" max="50"
                       value="{{ $radius }}" placeholder="0,5 - 5"
                       class="h-9 w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
            </div>

            <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Hitung</button>
        </form>
    </x-page-header>

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[360px_1fr]">

        {{-- Kolom kiri: panel hasil, ditukar tiap kali titik berganti. --}}
        <div id="panelHasil" class="flex flex-col gap-4 lg:h-[calc(100dvh-17rem)] lg:min-h-[420px]">
            @include('estimasi._hasil')
        </div>

        {{-- Kolom kanan: peta, tidak pernah dimuat ulang. --}}
        <div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:h-[calc(100dvh-17rem)] lg:min-h-[420px] dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2">
                <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Peta sebaran</h2>
                <div class="flex items-center gap-3 text-[10px] text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-blue-600"></span> titik dicari</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span> di bawah tengah</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-rose-500"></span> di atas tengah</span>
                </div>
            </div>

            {{-- Tinggi peta dipatok (2026-09-26, feedback user): dulu ikut tinggi
                     kolom kiri, jadi berubah-ubah tiap titik baru dipilih. --}}
                <div class="relative h-[420px] min-h-0 flex-1 border-t border-gray-100 dark:border-gray-700">
                <div id="petaEstimasi" class="absolute inset-0" style="background:#e5e7eb"></div>

                {{-- Penanda sedang memuat hasil baru sesudah klik peta. --}}
                <div id="petaSibuk" hidden
                     class="absolute left-1/2 top-3 z-[600] -translate-x-1/2 rounded-full bg-gray-900/80 px-3 py-1 text-[11px] font-medium text-white">
                    Menghitung&hellip;
                </div>

                {{-- Kotak rincian titik. Ditaruh di kiri bawah supaya tidak
                     menabrak tombol Peta/Satelit di kanan atas (2026-09-26,
                     feedback user). --}}
                <div id="petaIsi" hidden
                     class="absolute bottom-3 left-3 z-[600] max-h-[calc(100%-1.5rem)] w-[250px] overflow-y-auto rounded-md bg-white/95 p-3 shadow-lg ring-1 ring-black/5 dark:bg-gray-800/95 dark:ring-white/10">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-500">Nilai tanah</p>
                        <button type="button" id="rincTutup" aria-label="Tutup rincian"
                                class="-mr-1 -mt-1 grid h-5 w-5 place-items-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">&times;</button>
                    </div>
                    <p id="rincNilai" class="text-lg font-bold leading-tight tabular-nums text-gray-900 dark:text-gray-100"></p>
                    <p id="rincBanding" class="text-[11px]"></p>

                    <dl class="mt-2 space-y-1.5 border-t border-gray-100 pt-2 text-xs dark:border-gray-700">
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-500">Jenis</dt><dd id="rincJenis" class="text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-500">Tanggal penilaian</dt><dd id="rincTgl" class="text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-500">Luas tanah / bangunan</dt><dd id="rincLuas" class="tabular-nums text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-500">Jarak dari titik dicari</dt><dd id="rincJarak" class="tabular-nums text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-500">Lokasi</dt><dd id="rincLokasi" class="text-gray-800 dark:text-gray-200"></dd></div>
                        <div><dt class="text-[10px] text-gray-500 dark:text-gray-500">Nomor laporan</dt><dd id="rincNomor" class="break-all text-[10px] text-gray-600 dark:text-gray-400"></dd></div>
                    </dl>

                    <a id="rincMaps" href="#" target="_blank" rel="noopener"
                       class="mt-2 inline-block text-[11px] font-medium text-blue-600 hover:underline dark:text-blue-400">Buka di Google Maps &rarr;</a>
                </div>
            </div>

            <p class="border-t border-gray-100 px-4 py-2 text-[11px] text-gray-500 dark:border-gray-700 dark:text-gray-500">
                Klik di mana saja pada peta untuk menghitung titik itu. Klik titik berwarna untuk melihat rinciannya.
            </p>
        </div>
    </div>

    <p class="text-[11px] text-gray-500 dark:text-gray-500">
        Dihitung dari nilai <span class="font-medium">kesimpulan penilaian terdahulu</span> di sekitar titik, bukan data penawaran pasar.
        Titik yang lebih dekat dan lebih baru diberi bobot lebih besar; nilai sebenarnya jatuh di dalam rentang pada sekitar 8 dari 10 kasus.
        <span class="font-medium">Bukan pengganti analisis penilai dan tidak untuk dikutip sebagai pembanding di laporan.</span>
    </p>
</div>

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

    function tutupRincian() {
        isi.hidden = true;
        if (terpilih) {
            terpilih.setStyle({ weight: 1, color: terpilih.options.warnaAsli });
            terpilih = null;
        }
    }

    function tulis(t, penanda) {
        isi.hidden = false;

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
        document.getElementById('rincJarak').textContent  = jarakTeks(t.km);
        document.getElementById('rincLokasi').textContent = t.almt || [t.lk, t.kota].filter(Boolean).join(', ') || '—';
        document.getElementById('rincNomor').textContent  = t.no || '—';
        document.getElementById('rincMaps').href          = 'https://www.google.com/maps?q=' + t.la + ',' + t.lo;

        if (terpilih) terpilih.setStyle({ weight: 1, color: terpilih.options.warnaAsli });
        penanda.setStyle({ weight: 4, color: '#111827' });
        terpilih = penanda;
    }

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
            var penanda = L.circleMarker([t.la, t.lo], {
                radius: 7, color: warna, fillColor: warna, fillOpacity: 0.8, weight: 1, warnaAsli: warna
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

    // Hitung ulang satu koordinat tanpa memuat ulang halaman.
    var permintaan = 0;

    function hitung(lat, lon) {
        var nomor = ++permintaan;
        var koordinat = lat.toFixed(6) + ', ' + lon.toFixed(6);

        var parameter = new URLSearchParams();
        parameter.set('koordinat', koordinat);

        var kelompok = document.getElementById('kelompok').value;
        var radius   = document.getElementById('radius').value;
        if (kelompok) parameter.set('kelompok', kelompok);
        if (radius)   parameter.set('radius', radius);

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
})();
</script>
@endsection
