@extends('layouts.app')

@section('title', 'Estimasi Nilai Tanah')

@section('content')
{{-- Tata letak dipadatkan (2026-09-26, permintaan user): formulir masuk ke
     kartu judul, lalu rentang + tren + peta sejajar dalam satu baris supaya
     ketiganya terlihat tanpa menggulir. Tabel pembanding di bawah. --}}
<div class="mx-auto max-w-7xl space-y-4 py-5">

    <x-page-header title="Estimasi Nilai Tanah"
        subtitle="Rentang indikatif nilai tanah per m&sup2; dari titik koordinat &middot; {{ number_format($totalTitik, 0, ',', '.') }} titik data {{ $tahunData[0] }}&ndash;{{ $tahunData[1] }}">
        <form method="GET" action="{{ route('estimasi.index') }}" class="flex flex-wrap items-end gap-2">
            <div class="w-[230px]">
                <label for="koordinat" class="mb-0.5 block text-[11px] font-medium text-gray-500 dark:text-gray-500">Koordinat (dari Google Maps)</label>
                <input type="text" name="koordinat" id="koordinat" autofocus
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

            {{-- Radius diketik bebas; dikosongkan berarti 5 km. --}}
            <div class="w-[104px]">
                <label for="radius" class="mb-0.5 block text-[11px] font-medium text-gray-500 dark:text-gray-500">Radius (km)</label>
                <input type="number" name="radius" id="radius" step="0.1" min="0.1" max="50"
                       value="{{ $radius }}" placeholder="5"
                       class="h-9 w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
            </div>

            <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Hitung</button>
        </form>
    </x-page-header>

    @if ($galat)
        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
            {{ $galat }}
        </div>
    @endif

    @if ($lepasSaringan)
        <div class="rounded-lg border border-sky-300 bg-sky-50 px-4 py-2.5 text-sm text-sky-800 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
            Tidak ada pembanding <span class="font-medium">{{ strtolower(\App\Models\LandValuePoint::GROUPS[$kelompok] ?? '') }}</span> di sekitar titik ini,
            jadi hasil di bawah memakai <span class="font-medium">semua jenis properti</span>.
        </div>
    @endif

    @if ($hasil && $hasil['status'] === 'kosong')
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            {{ $hasil['pesan'] }}
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Data terbanyak ada di Jabodetabek. Coba lebarkan radius atau lepas saringan jenis properti.</p>
        </div>
    @endif

    @if ($hasil && $hasil['status'] !== 'kosong')
        @php
            $nada = [
                'tinggi' => 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
                'sedang' => 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
                'rendah' => 'bg-rose-100 text-rose-700 border-rose-300 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800',
            ][$hasil['keyakinan']];
            $rp = fn ($v) => 'Rp' . number_format($v, 0, ',', '.');
            $tren = $hasil['tren'] ?? null;
        @endphp

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[360px_1fr]">

            {{-- Kolom kiri: rentang lalu tren. --}}
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-500">Rentang indikatif per m&sup2;</p>
                        <span class="shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $nada }}">{{ $hasil['keyakinan'] }}</span>
                    </div>

                    <p class="mt-1 text-2xl font-bold leading-tight tabular-nums text-gray-900 dark:text-gray-100">
                        {{ $rp($hasil['bawah']) }}<span class="text-gray-400"> &ndash; </span>{{ $rp($hasil['atas']) }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        titik tengah <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $rp($hasil['tengah']) }}</span>
                    </p>

                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
                        <div>
                            <dt class="text-[11px] text-gray-500 dark:text-gray-500">Pembanding</dt>
                            <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $hasil['jumlah'] }} titik</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-gray-500 dark:text-gray-500">Cakupan</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $hasil['cakupan'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-gray-500 dark:text-gray-500">Tahun data</dt>
                            <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $hasil['tahun_min'] }}&ndash;{{ $hasil['tahun_maks'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-gray-500 dark:text-gray-500">Sebaran data</dt>
                            <dd class="text-xs font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($hasil['data_min']) }}&ndash;{{ $rp($hasil['data_maks']) }}</dd>
                        </div>
                    </dl>

                    @if ($hasil['dasar'] === 'wilayah')
                        <p class="mt-3 rounded-md bg-amber-50 px-2.5 py-1.5 text-[11px] text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            Tidak ada pembanding dalam radius itu. Angka memakai seluruh data {{ $hasil['cakupan'] }}, jadi gambaran kasar saja.
                        </p>
                    @endif
                </div>

                @if ($tren)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-baseline justify-between gap-2">
                            <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kecenderungan antar tahun</h2>
                            @if ($tren['status'] !== 'satu_tahun' && $tren['laju'] !== null)
                                <p class="text-sm font-bold tabular-nums {{ $tren['laju'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $tren['laju'] >= 0 ? '+' : '' }}{{ number_format($tren['laju'], 1, ',', '.') }}%<span class="font-normal text-gray-500 dark:text-gray-400">/tahun</span>
                                </p>
                            @endif
                        </div>

                        @if ($tren['status'] === 'satu_tahun')
                            <p class="mt-2 rounded-md bg-gray-50 px-2.5 py-1.5 text-xs text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                                {{ $tren['pesan'] }}
                            </p>
                        @else
                            @php $maks = max(array_column($tren['tahun'], 'tengah')) ?: 1; @endphp
                            <div class="mt-2 flex items-end gap-1.5">
                                @foreach ($tren['tahun'] as $t)
                                    <div class="flex flex-1 flex-col items-center gap-0.5"
                                         title="{{ $t['tahun'] }}: {{ $rp($t['tengah']) }} dari {{ $t['jumlah'] }} titik">
                                        <span class="text-[10px] tabular-nums text-gray-500 dark:text-gray-400">{{ number_format($t['tengah'] / 1000000, 1, ',', '.') }}jt</span>
                                        <div class="flex h-12 w-full items-end">
                                            <div class="w-full rounded-t bg-blue-500/80 dark:bg-blue-500"
                                                 style="height: {{ max(4, (int) round($t['tengah'] / $maks * 48)) }}px"></div>
                                        </div>
                                        <span class="text-[10px] font-semibold tabular-nums text-gray-600 dark:text-gray-400">{{ $t['tahun'] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($tren['pesan'])
                                <p class="mt-2 rounded-md bg-amber-50 px-2.5 py-1.5 text-[11px] text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                    {{ $tren['pesan'] }}
                                </p>
                            @endif
                        @endif
                    </div>
                @endif
            </div>

            {{-- Kolom kanan: peta. Kotak rincian melayang di atas peta supaya
                 tidak menambah tinggi halaman. --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2">
                    <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Peta sebaran</h2>
                    <div class="flex items-center gap-3 text-[10px] text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-blue-600"></span> titik dicari</span>
                        <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span> di bawah tengah</span>
                        <span class="inline-flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-rose-500"></span> di atas tengah</span>
                    </div>
                </div>

                <div class="relative border-t border-gray-100 dark:border-gray-700">
                    <div id="petaEstimasi" class="h-[320px] w-full lg:h-[430px]" style="background:#e5e7eb"></div>

                    <div id="petaRincian" class="pointer-events-none absolute inset-y-2 right-2 z-[500] w-[250px]">
                        <p id="petaKosong" class="rounded-md bg-white/90 px-3 py-2 text-[11px] text-gray-500 shadow dark:bg-gray-800/90 dark:text-gray-400">
                            Klik titik di peta untuk rinciannya.
                        </p>

                        <div id="petaIsi" hidden
                             class="pointer-events-auto max-h-full overflow-y-auto rounded-md bg-white/95 p-3 shadow-lg ring-1 ring-black/5 dark:bg-gray-800/95 dark:ring-white/10">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-500">Nilai tanah</p>
                                <button type="button" id="rincTutup" aria-label="Tutup"
                                        class="-mt-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">&times;</button>
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
                </div>
            </div>
        </div>

        <p class="text-[11px] text-gray-500 dark:text-gray-500">
            Dihitung dari nilai <span class="font-medium">kesimpulan penilaian terdahulu</span> di sekitar titik, bukan data penawaran pasar.
            Titik yang lebih dekat dan lebih baru diberi bobot lebih besar; nilai sebenarnya jatuh di dalam rentang pada sekitar 8 dari 10 kasus.
            <span class="font-medium">Bukan pengganti analisis penilai dan tidak untuk dikutip sebagai pembanding di laporan.</span>
        </p>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between px-5 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pembanding terdekat</h2>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $hasil['pembanding']->count() }} titik</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-5 py-2.5">Jarak</th>
                            <th class="px-4 py-2.5">Nilai / m&sup2;</th>
                            <th class="px-4 py-2.5">Jenis</th>
                            <th class="px-4 py-2.5">Tahun</th>
                            <th class="px-5 py-2.5">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($hasil['pembanding']->take(25) as $baris)
                            @php $p = $baris['titik']; @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-2.5 tabular-nums text-gray-700 dark:text-gray-300">
                                    {{ $baris['jarak'] < 1
                                        ? number_format($baris['jarak'] * 1000, 0, ',', '.') . ' m'
                                        : number_format($baris['jarak'], 1, ',', '.') . ' km' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                                    {{ $rp($p->land_rate) }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ $p->property_type ?: $p->group_label }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 tabular-nums text-gray-600 dark:text-gray-400">{{ $p->valuation_year }}</td>
                                <td class="px-5 py-2.5 text-gray-600 dark:text-gray-400">
                                    <a href="https://www.google.com/maps?q={{ $p->latitude }},{{ $p->longitude }}" target="_blank" rel="noopener"
                                       class="text-blue-600 hover:underline dark:text-blue-400">{{ $p->village ?: '—' }}</a>
                                    <span class="text-gray-400 dark:text-gray-500">&middot; {{ $p->district }}, {{ $p->city }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($hasil['pembanding']->count() > 25)
                <p class="border-t border-gray-100 px-5 py-2.5 text-xs text-gray-400 dark:border-gray-700 dark:text-gray-500">
                    Menampilkan 25 terdekat dari {{ $hasil['pembanding']->count() }} pembanding yang dipakai.
                </p>
            @endif
        </div>

        @php
            // Disiapkan di sini supaya baris @json tetap satu ekspresi pendek.
            $petaTitik = $hasil['pembanding']->take(200)->map(fn ($b) => [
                'la' => (float) $b['titik']->latitude,
                'lo' => (float) $b['titik']->longitude,
                'rp' => (int) $b['titik']->land_rate,
                'th' => (int) $b['titik']->valuation_year,
                'tgl' => $b['titik']->valuation_date?->translatedFormat('d F Y'),
                'jn' => $b['titik']->property_type ?: $b['titik']->group_label,
                'km' => round($b['jarak'], 2),
                'lk' => trim(($b['titik']->village ?: '') . ', ' . ($b['titik']->district ?: '')),
                'kota' => $b['titik']->city,
                'almt' => $b['titik']->address,
                'lt' => $b['titik']->land_area,
                'lb' => $b['titik']->building_area,
                'no' => $b['titik']->report_number,
            ])->values();
        @endphp

        {{-- Leaflet dimuat hanya di halaman ini, dan hanya bila ada hasil. --}}
        <link rel="stylesheet" href="{{ asset('js/leaflet/leaflet.css') }}">
        <script src="{{ asset('js/leaflet/leaflet.js') }}"></script>
        <script>
        (function () {
            var wadah = document.getElementById('petaEstimasi');
            if (!wadah || typeof L === 'undefined') return;

            var pusat = @json($titik);
            var radius = {{ $hasil['dasar'] === 'radius' ? (float) $hasil['cakupan'] : 0 }};
            var tengah = {{ $hasil['tengah'] }};
            var titik = @json($petaTitik);

            var peta = L.map(wadah, {
                scrollWheelZoom: true,      // zoom bebas seperti Google Maps
                zoomControl: true,
            });

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

            // Pilihan Peta / Satelit, sama seperti tombol di Google Maps.
            L.control.layers({
                'Peta': jalan,
                'Satelit': L.layerGroup([satelit, label]),
            }, null, { position: 'topright' }).addTo(peta);

            var rupiah = function (n) { return 'Rp' + n.toLocaleString('id-ID'); };
            var jarakTeks = function (km) { return km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(2).replace('.', ',') + ' km'; };

            // ---- kotak rincian ----
            var kosong = document.getElementById('petaKosong');
            var isi    = document.getElementById('petaIsi');
            var terpilih = null;

            document.getElementById('rincTutup').addEventListener('click', function () {
                isi.hidden = true;
                kosong.hidden = false;
                if (terpilih) {
                    terpilih.setStyle({ weight: 1, color: terpilih.options.warnaAsli });
                    terpilih = null;
                }
            });

            function tulis(t, penanda) {
                kosong.hidden = true;
                isi.hidden = false;

                document.getElementById('rincNilai').textContent = rupiah(t.rp) + ' /m²';

                var selisih = tengah ? Math.round((t.rp - tengah) / tengah * 100) : 0;
                var banding = document.getElementById('rincBanding');
                banding.textContent = selisih === 0
                    ? 'sama dengan titik tengah'
                    : (selisih > 0 ? '+' : '') + selisih + '% terhadap titik tengah';
                banding.className = 'text-xs ' + (selisih > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400');

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

            var batas = [[pusat[0], pusat[1]]];

            var penandaSemua = [];

            titik.forEach(function (t) {
                var warna = t.rp > tengah ? '#e11d48' : '#10b981';
                var penanda = L.circleMarker([t.la, t.lo], {
                    radius: 7, color: warna, fillColor: warna, fillOpacity: 0.8, weight: 1, warnaAsli: warna
                }).addTo(peta);

                function buka(e) {
                    // Klik pada titik TIDAK boleh ikut memicu klik peta, kalau
                    // tidak yang muncul malah popup "Hitung dari titik ini"
                    // (2026-09-26, feedback user).
                    if (e) L.DomEvent.stopPropagation(e);
                    peta.closePopup();
                    tulis(t, penanda);
                }

                penanda.on('click', buka);
                penanda.bindTooltip(rupiah(t.rp), { direction: 'top' });

                // Lingkaran bening yang lebih besar: titik berdiameter 14 px
                // terlalu kecil untuk jari/kursor, meleset sedikit saja klik
                // jatuh ke peta (2026-09-26, feedback user).
                // fillOpacity 0.01, bukan 0: bidang dengan opacity nol tidak
                // selalu menerima klik di semua peramban.
                L.circleMarker([t.la, t.lo], {
                    radius: 18, opacity: 0, fillOpacity: 0.01, fillColor: warna, weight: 0, interactive: true
                }).addTo(peta).on('click', buka);

                penandaSemua.push({ data: t, penanda: penanda, buka: buka });
                batas.push([t.la, t.lo]);
            });

            // Titik yang dicari + lingkaran radius yang benar-benar dipakai.
            L.circleMarker([pusat[0], pusat[1]], {
                radius: 9, color: '#1d4ed8', fillColor: '#2563eb', fillOpacity: 1, weight: 2
            }).addTo(peta).bindTooltip('Titik yang dicari', { direction: 'top' });

            if (radius > 0) {
                L.circle([pusat[0], pusat[1]], {
                    radius: radius * 1000,
                    color: '#2563eb', weight: 1, fillColor: '#3b82f6', fillOpacity: 0.06
                }).addTo(peta);
            }

            // Klik di area kosong = hitung ulang dari titik itu, seperti
            // memindahkan pin di Google Maps (2026-09-26, permintaan user).
            peta.on('click', function (e) {
                // Klik yang jatuh dekat sebuah titik dianggap mengklik titik itu.
                var layar = peta.latLngToContainerPoint(e.latlng), dekat = null, jarakPx = 1e9;

                penandaSemua.forEach(function (x) {
                    var p = peta.latLngToContainerPoint(x.penanda.getLatLng());
                    var d = Math.hypot(p.x - layar.x, p.y - layar.y);
                    if (d < jarakPx) { jarakPx = d; dekat = x; }
                });

                if (dekat && jarakPx <= 32) {
                    dekat.buka();
                    return;
                }

                var la = e.latlng.lat.toFixed(6), lo = e.latlng.lng.toFixed(6);
                var url = new URL(window.location.href);
                url.searchParams.set('koordinat', la + ', ' + lo);

                L.popup()
                    .setLatLng(e.latlng)
                    .setContent(
                        '<div style="text-align:center">' + la + ', ' + lo + '<br>' +
                        '<a href="' + url.toString() + '" style="color:#2563eb;font-weight:600">Hitung dari titik ini &rarr;</a></div>'
                    )
                    .openOn(peta);
            });

            peta.fitBounds(L.latLngBounds(batas).pad(0.15), { maxZoom: 17 });
        })();
        </script>
    @endif

    @unless ($hasil)
        <div class="rounded-lg border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
            Tempel koordinat lokasi, lalu tekan <span class="font-medium">Hitung</span>.
        </div>
    @endunless
</div>
@endsection
