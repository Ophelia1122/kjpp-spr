{{-- Kartu mini profile Beranda (2026-09-19, feedback user): menggantikan baris
     "Halo, ...". Satu kartu memanjang: avatar + sapaan di kiri, angka kinerja
     di kanan. Tinggi dijaga tetap rendah supaya konten di bawahnya tidak turun.
     Butuh $profile dari DashboardController@miniProfile. --}}
@php
    $stats = [
        ['label' => 'SLA Draft', 'value' => $profile['avg_days'] !== null ? $profile['avg_days'] . ' hr' : '—',
         'sub' => $profile['on_time_pct'] !== null ? $profile['on_time_pct'] . '% tepat' : 'belum ada data',
         'tone' => ($profile['on_time_pct'] ?? 100) >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
         'tip' => 'Rata-rata hari dari tanggal survei sampai nilai diajukan (1 tahun terakhir, ' . $profile['sample'] . ' proyek)'],
        ['label' => 'Survei bulan ini', 'value' => $profile['month_survey'], 'sub' => 'proyek', 'tone' => 'text-gray-900 dark:text-gray-100',
         'tip' => 'Proyek dengan tanggal survei di bulan berjalan'],
        ['label' => 'Selesai bulan ini', 'value' => $profile['month_done'], 'sub' => 'buku cetak', 'tone' => 'text-gray-900 dark:text-gray-100',
         'tip' => 'Proyek yang bukunya dicetak di bulan berjalan'],
    ];

    if ($profile['review_queue'] !== null) {
        $stats[] = ['label' => 'Antre review', 'value' => $profile['review_queue'], 'sub' => 'proyek',
                    'tone' => $profile['review_queue'] > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-900 dark:text-gray-100',
                    'tip' => 'Nilai & draft laporan yang menunggu review'];
        $stats[] = ['label' => 'Direview bulan ini', 'value' => $profile['review_done'], 'sub' => 'proyek',
                    'tone' => 'text-gray-900 dark:text-gray-100', 'tip' => 'Nilai yang Anda setujui di bulan berjalan'];
    }
@endphp

<div class="flex flex-wrap items-center gap-x-5 gap-y-3 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex min-w-0 items-center gap-3">
        @include('partials.user-avatar', ['avatarUser' => auth()->user(), 'avatarClass' => 'h-11 w-11 bg-blue-600 text-base'])
        <div class="min-w-0">
            <p class="truncate text-lg font-bold leading-tight text-gray-900 dark:text-gray-100">
                Halo, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}! 👋
            </p>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                {{ $profile['office_wide'] ? 'Angka seluruh kantor' : 'Angka Anda' }} &middot; {{ now()->locale('id')->translatedFormat('l, d F Y') }}
            </p>
        </div>
    </div>

    <div class="ml-auto flex flex-wrap items-center gap-x-5 gap-y-2">
        @foreach ($stats as $s)
            <div class="min-w-[86px] border-l border-gray-100 pl-4 first:border-0 first:pl-0 dark:border-gray-700" title="{{ $s['tip'] }}">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $s['label'] }}</p>
                <p class="text-lg font-bold leading-tight {{ $s['tone'] }}">{{ $s['value'] }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $s['sub'] }}</p>
            </div>
        @endforeach
    </div>
</div>
