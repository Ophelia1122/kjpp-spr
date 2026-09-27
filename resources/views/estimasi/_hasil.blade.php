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
        Tidak ada pembanding <span class="font-medium">{{ strtolower(\App\Models\LandValuePoint::KELAS_LABELS[$kelas] ?? '') }}</span> di sekitar titik ini,
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
            @php
                // Satuannya ikut kelas properti: Ruko & Apart/OS/Kios dihitung
                // per m² bangunan, sisanya per m² tanah (2026-09-27).
                $satuan = $hasil['satuan'] ?? ['tanah'];
                $satuanTeks = count($satuan) > 1
                    ? 'campuran satuan'
                    : (\App\Models\LandValuePoint::SATUAN_LABELS[$satuan[0]] ?? 'per m²');
            @endphp
            <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Rentang indikatif {{ $satuanTeks }}</p>
            <span class="shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $nada }}"
                  data-catatan-judul="Tingkat keyakinan"
                  data-catatan="Dinilai dari jumlah pembanding, jaraknya, dan sebaran harganya. Makin tinggi, makin rapat datanya.">{{ $hasil['keyakinan'] }}</span>
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

        @if (count($satuan) > 1)
            <p class="mt-2 rounded-md bg-amber-50 px-2 py-1 text-[10px] leading-tight text-amber-800 sm:truncate sm:text-[9px] dark:bg-amber-900/20 dark:text-amber-300"
               data-catatan-nada="peringatan"
               data-catatan-judul="Satuan campur"
               data-catatan="Pembandingnya memakai harga per m² tanah dan per m² bangunan sekaligus, jadi angkanya tidak setara. Pilih satu jenis properti supaya satuannya seragam.">
                Angka di atas tidak bisa dibaca langsung, pilih jenis properti agar sesuai.
            </p>
        @endif

        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
            <div>
                <dt class="text-[11px] text-gray-500 dark:text-gray-400">Cakupan</dt>
                <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $hasil['cakupan'] }}
                    @isset($hasil['faktor'])
                        <span class="font-normal text-gray-500 dark:text-gray-400">&middot; &divide;&times;{{ number_format($hasil['faktor'], 2, ',', '.') }}</span>
                    @endisset
                </dd>
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
        {{-- Tingginya dikunci. Jumlah batang dan ada-tidaknya catatan berbeda
             tiap titik, dan dulu itu membuat tabel pembanding di bawahnya
             berubah ukuran terus (2026-09-27, feedback user). --}}
        <div class="kartu-tren relative h-[144px] shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-baseline justify-between gap-2">
                <h2 class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Kecenderungan antar tahun

                    {{-- Catatan dipindah ke lencana ini: dulu paragraf di bawah
                         batang, dan panjangnya berubah-ubah (2026-09-27,
                         permintaan user). Rinciannya muncul saat kursor
                         mengambang, di kotak sendiri. --}}
                    @if ($tren['status'] !== 'satu_tahun' && $tren['pesan'])
                        <button type="button" class="tanda-catatan -m-1 p-1" aria-label="Catatan tentang data tren">
                            <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.19-1.458-1.516-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    @endif
                </h2>
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
                             data-catatan-judul="Tahun {{ $t['tahun'] }}"
                             data-catatan="Titik tengah {{ $rp($t['tengah']) }} dari {{ $t['jumlah'] }} pembanding.">
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
                    <div class="kotak-catatan absolute inset-x-3 top-9 z-20 rounded-md border border-amber-200 bg-amber-50 p-2.5 text-[11px] leading-snug text-amber-900 shadow-lg dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Catatan data</p>
                        {{ $tren['pesan'] }}
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- Tabel pembanding ikut berganti tiap titik baru dipilih. --}}
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between px-4 py-2.5">
            <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pembanding terdekat</h2>
            <div class="flex items-center gap-2">
                @php $tampil = min(100, $hasil['pembanding']->count()); @endphp
                <span class="text-[11px] text-gray-400 dark:text-gray-400"
                      data-catatan-judul="Jumlah pembanding"
                      data-catatan="Tabel memuat {{ $tampil }} pembanding terdekat. Hitungan dan unduhan Excel memakai seluruh {{ $hasil['pembanding']->count() }} titik.">
                    {{ $tampil < $hasil['pembanding']->count()
                        ? $tampil . ' dari ' . $hasil['pembanding']->count()
                        : $hasil['pembanding']->count() }} titik
                </span>
                {{-- Kertas kerja pembanding dalam radius (2026-09-27, permintaan user). --}}
                <a href="{{ route('estimasi.export', request()->only(['koordinat', 'kelas', 'sumber', 'tujuan', 'radius', 'tahun_min', 'tahun_max'])) }}"
                   data-catatan-judul="Unduh Excel"
                   data-catatan="Kertas kerja berisi seluruh pembanding dalam radius, satu kolom per data."
                   class="inline-flex items-center gap-1 rounded-md border border-emerald-200 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-emerald-900/30">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Excel
                </a>
            </div>
        </div>
        <div class="max-h-[280px] min-h-[120px] flex-1 overflow-y-auto lg:max-h-none">
            <table class="w-full text-xs">
                <thead class="sticky top-0 border-y border-gray-100 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    <tr class="text-left text-[10px] font-semibold uppercase tracking-wide">
                        <th class="px-4 py-2">Jarak</th>
                        <th class="px-3 py-2">Nilai / m&sup2;</th>
                        <th class="px-3 py-2">Tahun</th>
                        <th class="px-4 py-2">Objek</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($hasil['pembanding']->take(100) as $baris)
                        @php $p = $baris['titik']; @endphp
                        <tr>
                            {{-- Jaraknya sekaligus tautan ke titiknya di Google Maps;
                                 kolom Lokasi diganti Objek karena lebih menentukan
                                 (2026-09-27, permintaan user). --}}
                            <td class="whitespace-nowrap px-4 py-2 tabular-nums">
                                <a href="https://www.google.com/maps?q={{ $p->latitude }},{{ $p->longitude }}"
                                   target="_blank" rel="noopener"
                                   data-catatan-judul="{{ trim(($p->village ?: '') . ', ' . ($p->district ?: ''), ', ') ?: 'Lokasi' }}"
                                   data-catatan="Buka {{ $p->latitude }}, {{ $p->longitude }} di Google Maps."
                                   class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $baris['jarak'] < 1
                                        ? number_format($baris['jarak'] * 1000, 0, ',', '.') . ' m'
                                        : number_format($baris['jarak'], 1, ',', '.') . ' km' }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($p->land_rate) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 tabular-nums text-gray-600 dark:text-gray-300">{{ $p->valuation_year }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300"
                                data-catatan-judul="Lokasi"
                                data-catatan="{{ trim(($p->village ?: '') . ', ' . ($p->district ?: '') . ', ' . ($p->city ?: ''), ', ') ?: 'Tidak tercatat' }}">
                                {{ $p->property_type ?: $p->kelas_label }}
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
                'tipe' => $b['titik']->data_type,
                'jenisTransaksi' => $b['titik']->offer_type,
                'namaSumber'     => $b['titik']->source_name,
                'statusSumber'   => $b['titik']->source_status,
                'telepon'        => $b['titik']->source_phone,
            ])->values()
            : [],
    ];
@endphp
<script type="application/json" id="dataPeta">@json($petaData)</script>
