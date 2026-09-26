{{-- Panel hasil: rentang, tren, tabel pembanding, dan data untuk peta.
     Ditukar lewat fetch saat pengguna mengklik peta (2026-09-26, permintaan
     user) sehingga peta tidak ikut dimuat ulang. --}}
@php
    $rp = fn ($v) => 'Rp' . number_format($v, 0, ',', '.');
@endphp

@if ($galat)
    <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
        {{ $galat }}
    </div>
@endif

@if ($lepasSaringan)
    <div class="rounded-lg border border-sky-300 bg-sky-50 px-3 py-2 text-xs text-sky-800 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
        Tidak ada pembanding <span class="font-medium">{{ strtolower(\App\Models\LandValuePoint::GROUPS[$kelompok] ?? '') }}</span> di sekitar titik ini,
        jadi hasil memakai <span class="font-medium">semua jenis properti</span>.
    </div>
@endif

@unless ($hasil)
    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
        Klik titik mana pun di peta, atau tempel koordinat di atas.
    </div>
@endunless

@if ($hasil && $hasil['status'] === 'kosong')
    <div class="rounded-lg border border-gray-200 bg-white px-4 py-6 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
        {{ $hasil['pesan'] }}
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">Coba lebarkan radius atau lepas saringan jenis properti.</p>
    </div>
@endif

@if ($hasil && $hasil['status'] !== 'kosong')
    @php
        $nada = [
            'tinggi' => 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
            'sedang' => 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
            'rendah' => 'bg-rose-100 text-rose-700 border-rose-300 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800',
        ][$hasil['keyakinan']];
        $tren = $hasil['tren'] ?? null;
    @endphp

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-start justify-between gap-2">
            <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Rentang indikatif per m&sup2;</p>
            <span class="shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $nada }}" title="Tingkat keyakinan">{{ $hasil['keyakinan'] }}</span>
        </div>

        @php
            // Ukuran huruf menyesuaikan panjang angka supaya tetap satu baris
            // (2026-09-26, feedback user): nilai ratusan juta pakai huruf kecil.
            $panjang = mb_strlen($rp($hasil['bawah']) . $rp($hasil['atas']));
            $ukuran  = $panjang > 26 ? 'text-lg' : ($panjang > 22 ? 'text-xl' : 'text-2xl');
        @endphp
        <p class="mt-1 whitespace-nowrap {{ $ukuran }} font-bold leading-tight tabular-nums text-gray-900 dark:text-gray-100">
            {{ $rp($hasil['bawah']) }}<span class="text-gray-400"> &ndash; </span>{{ $rp($hasil['atas']) }}
        </p>
        <p class="text-xs text-gray-600 dark:text-gray-300">
            titik tengah <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $rp($hasil['tengah']) }}</span>
        </p>

        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
            <div>
                <dt class="text-[11px] text-gray-500 dark:text-gray-400">Pembanding</dt>
                <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $hasil['jumlah'] }} titik</dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-500 dark:text-gray-400">Cakupan</dt>
                <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $hasil['cakupan'] }}</dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-500 dark:text-gray-400">Tahun data</dt>
                <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $hasil['tahun_min'] }}&ndash;{{ $hasil['tahun_maks'] }}</dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-500 dark:text-gray-400">Sebaran data</dt>
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
                            <span class="text-[10px] font-semibold tabular-nums text-gray-600 dark:text-gray-300">{{ $t['tahun'] }}</span>
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

    {{-- Tabel pembanding ikut berganti tiap titik baru dipilih. --}}
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between px-4 py-2.5">
            <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pembanding terdekat</h2>
            <span class="text-[11px] text-gray-400 dark:text-gray-400">{{ $hasil['pembanding']->count() }} titik</span>
        </div>
        <div class="max-h-[280px] min-h-0 flex-1 overflow-y-auto lg:max-h-none">
            <table class="w-full text-xs">
                <thead class="sticky top-0 border-y border-gray-100 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    <tr class="text-left text-[10px] font-semibold uppercase tracking-wide">
                        <th class="px-4 py-2">Jarak</th>
                        <th class="px-3 py-2">Nilai / m&sup2;</th>
                        <th class="px-3 py-2">Tahun</th>
                        <th class="px-4 py-2">Lokasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($hasil['pembanding']->take(25) as $baris)
                        @php $p = $baris['titik']; @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-2 tabular-nums text-gray-700 dark:text-gray-300">
                                {{ $baris['jarak'] < 1
                                    ? number_format($baris['jarak'] * 1000, 0, ',', '.') . ' m'
                                    : number_format($baris['jarak'], 1, ',', '.') . ' km' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($p->land_rate) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 tabular-nums text-gray-600 dark:text-gray-300">{{ $p->valuation_year }}</td>
                            {{-- Lokasi membuka titiknya di Google Maps. --}}
                            <td class="px-4 py-2">
                                <a href="https://www.google.com/maps?q={{ $p->latitude }},{{ $p->longitude }}"
                                   target="_blank" rel="noopener"
                                   title="Buka {{ $p->latitude }}, {{ $p->longitude }} di Google Maps"
                                   class="text-blue-600 hover:underline dark:text-blue-400">{{ $p->village ?: '—' }}, {{ $p->district }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Data titik untuk peta. Dibaca JS tiap kali panel ini diganti. --}}
@php
    $petaData = [
        'pusat'  => $titik,
        'radius' => $hasil && ($hasil['dasar'] ?? '') === 'radius'
            ? (float) str_replace(',', '.', $hasil['cakupan'])
            : 0,
        'tengah' => $hasil['tengah'] ?? 0,
        'titik'  => $hasil && $hasil['status'] !== 'kosong'
            ? $hasil['pembanding']->take(200)->map(fn ($b) => [
                'la'   => (float) $b['titik']->latitude,
                'lo'   => (float) $b['titik']->longitude,
                'rp'   => (int) $b['titik']->land_rate,
                'th'   => (int) $b['titik']->valuation_year,
                'tgl'  => $b['titik']->valuation_date?->translatedFormat('d F Y'),
                'jn'   => $b['titik']->property_type ?: $b['titik']->group_label,
                'km'   => round($b['jarak'], 2),
                'lk'   => trim(($b['titik']->village ?: '') . ', ' . ($b['titik']->district ?: '')),
                'kota' => $b['titik']->city,
                'almt' => $b['titik']->address,
                'lt'   => $b['titik']->land_area,
                'lb'   => $b['titik']->building_area,
                'no'   => $b['titik']->report_number,
            ])->values()
            : [],
    ];
@endphp
<script type="application/json" id="dataPeta">@json($petaData)</script>
