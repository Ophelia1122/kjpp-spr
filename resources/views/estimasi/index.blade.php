@extends('layouts.app')

@section('title', 'Estimasi Nilai Tanah')

@section('content')
{{-- Halaman sengaja ringan (2026-09-26, permintaan user): tanpa peta & tanpa
     ekspor supaya cepat dibuka. Cukup tempel koordinat, langsung dapat
     rentang beserta pembandingnya. --}}
<div class="max-w-5xl mx-auto py-8 space-y-6">

    <x-page-header title="Estimasi Nilai Tanah"
        subtitle="Rentang indikatif nilai tanah per m&sup2; dari titik koordinat">
        <div class="text-right">
            <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-500">Titik data</p>
            <p class="text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($totalTitik, 0, ',', '.') }}</p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $tahunData[0] }}&ndash;{{ $tahunData[1] }}</p>
        </div>
    </x-page-header>

    <form method="GET" action="{{ route('estimasi.index') }}"
          class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="min-w-[240px] flex-1">
            <label for="koordinat" class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-500">Koordinat</label>
            <input type="text" name="koordinat" id="koordinat" autofocus
                   value="{{ request('koordinat') }}"
                   placeholder="-6.304484, 106.805611"
                   class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Tempel langsung dari Google Maps (klik kanan lokasi &rarr; klik koordinatnya).</p>
        </div>

        <div class="min-w-[170px]">
            <label for="kelompok" class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-500">Jenis properti</label>
            <select name="kelompok" id="kelompok" class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                <option value="">Semua jenis</option>
                @foreach (\App\Models\LandValuePoint::GROUPS as $kunci => $label)
                    <option value="{{ $kunci }}" @selected($kelompok === $kunci)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="min-w-[130px]">
            <label for="radius" class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-500">Radius maks.</label>
            <select name="radius" id="radius" class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                <option value="">Sampai 5 km</option>
                @foreach (['0.5' => '0,5 km', '1' => '1 km', '2' => '2 km', '3' => '3 km'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected((string) $radius === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Hitung</button>
    </form>

    @if ($galat)
        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
            {{ $galat }}
        </div>
    @endif

    @if ($lepasSaringan)
        <div class="rounded-lg border border-sky-300 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
            Tidak ada pembanding <span class="font-medium">{{ strtolower(\App\Models\LandValuePoint::GROUPS[$kelompok] ?? '') }}</span> di sekitar titik ini,
            jadi hasil di bawah memakai <span class="font-medium">semua jenis properti</span>.
        </div>
    @endif

    @if ($hasil && $hasil['status'] === 'kosong')
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            {{ $hasil['pesan'] }}
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Data terbanyak ada di Jabodetabek. Coba lepas saringan jenis properti.</p>
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
        @endphp

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-500">Rentang indikatif nilai tanah</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
                        {{ $rp($hasil['bawah']) }} &ndash; {{ $rp($hasil['atas']) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        per m&sup2; &middot; titik tengah <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $rp($hasil['tengah']) }}</span>
                    </p>
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $nada }}">
                    Keyakinan {{ $hasil['keyakinan'] }}
                </span>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 text-sm sm:grid-cols-4 dark:border-gray-700">
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-500">Pembanding</dt>
                    <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $hasil['jumlah'] }} titik</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-500">Cakupan</dt>
                    <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $hasil['cakupan'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-500">Tahun data</dt>
                    <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $hasil['tahun_min'] }}&ndash;{{ $hasil['tahun_maks'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-500">Sebaran data</dt>
                    <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($hasil['data_min']) }} &ndash; {{ $rp($hasil['data_maks']) }}</dd>
                </div>
            </dl>

            @if ($hasil['dasar'] === 'wilayah')
                <p class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                    Tidak ada pembanding dalam radius 5 km. Angka di atas memakai seluruh data {{ $hasil['cakupan'] }},
                    jadi tidak mewakili lokasi persisnya &mdash; pakai sebagai gambaran kasar saja.
                </p>
            @endif

            <p class="mt-4 text-xs text-gray-500 dark:text-gray-500">
                Dihitung dari nilai <span class="font-medium">kesimpulan penilaian terdahulu</span> di sekitar titik, bukan data penawaran pasar.
                Titik yang lebih dekat dan lebih baru diberi bobot lebih besar. Rentangnya diuji ulang terhadap seluruh data:
                nilai sebenarnya jatuh di dalam rentang pada sekitar 8 dari 10 kasus.
                <span class="font-medium">Bukan pengganti analisis penilai dan tidak untuk dikutip sebagai pembanding di laporan.</span>
            </p>
        </div>

        {{-- Peta sebaran pembanding (2026-09-26, permintaan user): biar kelihatan
             titik mana saja yang dipakai. Leaflet disimpan lokal seperti Tailwind,
             tidak menarik dari CDN; ubin peta tetap dari OpenStreetMap, jadi kalau
             NAS sedang tanpa internet ubinnya kosong tetapi titiknya tetap tampil. --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Peta sebaran</h2>
                <div class="flex items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-600"></span> titik dicari</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span> di bawah tengah</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-rose-500"></span> di atas tengah</span>
                </div>
            </div>
            <div id="petaEstimasi" class="h-[380px] w-full border-t border-gray-100 dark:border-gray-700" style="background:#e5e7eb"></div>
        </div>

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
                'jn' => $b['titik']->property_type ?: $b['titik']->group_label,
                'km' => round($b['jarak'], 2),
                'lk' => trim(($b['titik']->village ?: '') . ', ' . ($b['titik']->district ?: '')),
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

            var peta = L.map(wadah, { scrollWheelZoom: false });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(peta);

            var rupiah = function (n) { return 'Rp' + n.toLocaleString('id-ID'); };

            var batas = [[pusat[0], pusat[1]]];

            titik.forEach(function (t) {
                L.circleMarker([t.la, t.lo], {
                    radius: 6,
                    color: t.rp > tengah ? '#e11d48' : '#10b981',
                    fillColor: t.rp > tengah ? '#e11d48' : '#10b981',
                    fillOpacity: 0.75,
                    weight: 1
                }).addTo(peta).bindPopup(
                    '<b>' + rupiah(t.rp) + '</b> /m&sup2;<br>' +
                    t.jn + ' &middot; ' + t.th + '<br>' +
                    t.lk + '<br>' +
                    '<span style="color:#6b7280">' + (t.km < 1 ? Math.round(t.km * 1000) + ' m' : t.km + ' km') + ' dari titik</span>'
                );
                batas.push([t.la, t.lo]);
            });

            // Titik yang dicari + lingkaran radius yang benar-benar dipakai.
            L.circleMarker([pusat[0], pusat[1]], {
                radius: 8, color: '#1d4ed8', fillColor: '#2563eb', fillOpacity: 1, weight: 2
            }).addTo(peta).bindPopup('Titik yang dicari');

            if (radius > 0) {
                L.circle([pusat[0], pusat[1]], {
                    radius: radius * 1000,
                    color: '#2563eb', weight: 1, fillColor: '#3b82f6', fillOpacity: 0.06
                }).addTo(peta);
            }

            peta.fitBounds(L.latLngBounds(batas).pad(0.15), { maxZoom: 16 });
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
