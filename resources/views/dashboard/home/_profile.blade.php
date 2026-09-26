{{-- Kartu mini profile Beranda (2026-09-19, feedback user): menggantikan baris
     "Halo, ...". Satu kartu memanjang: avatar + sapaan di kiri, angka kinerja
     di kanan. Tinggi dijaga tetap rendah supaya konten di bawahnya tidak turun.
     Butuh $profile dari DashboardController@miniProfile. --}}
@php
    // Admin Produksi (mode kantor) memakai angka SLA Laporan Final; peran lain
    // memakai SLA Draft & survei (2026-09-24, feedback user).
    if ($profile['final_mode'] ?? false) {
        $stats = [
            ['label' => 'SLA Final', 'value' => $profile['final_avg_days'] !== null ? $profile['final_avg_days'] . ' hr' : '—',
             'sub' => $profile['final_on_time_pct'] !== null ? $profile['final_on_time_pct'] . '% tepat' : 'belum ada data',
             'tone' => ($profile['final_on_time_pct'] ?? 100) >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
             'tip' => 'Rata-rata hari dari resume disetujui sampai buku dicetak (buku dicetak bulan ini, ' . $profile['final_sample'] . ' proyek)'],
            ['label' => 'SLA Final sesuai', 'value' => $profile['final_ok'], 'sub' => 'proyek',
             'tone' => 'text-emerald-600 dark:text-emerald-400',
             'tip' => 'Buku dicetak bulan ini dan tidak melewati target Laporan Final'],
            ['label' => 'SLA Final lewat', 'value' => $profile['final_late'], 'sub' => 'proyek',
             'tone' => $profile['final_late'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-gray-100',
             'tip' => 'Buku dicetak bulan ini tetapi melewati target Laporan Final'],
        ];
    } else {
    $stats = [
        ['label' => 'SLA Draft', 'value' => $profile['avg_days'] !== null ? $profile['avg_days'] . ' hr' : '—',
         'sub' => $profile['on_time_pct'] !== null ? $profile['on_time_pct'] . '% tepat' : 'belum ada data',
         'tone' => ($profile['on_time_pct'] ?? 100) >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
         'tip' => 'Rata-rata hari dari tanggal survei sampai nilai diajukan (nilai diajukan bulan ini, ' . $profile['sample'] . ' proyek)'],
        ['label' => 'Survei bulan ini', 'value' => $profile['month_survey'], 'sub' => 'proyek', 'tone' => 'text-gray-900 dark:text-gray-100',
         'tip' => 'Proyek dengan tanggal survei di bulan berjalan'],
        ['label' => 'Selesai bulan ini', 'value' => $profile['month_done'], 'sub' => 'buku cetak', 'tone' => 'text-gray-900 dark:text-gray-100',
         'tip' => 'Proyek yang bukunya dicetak di bulan berjalan'],
    ];
    }

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
                Hari ini &middot; {{ now()->locale('id')->translatedFormat('l, d F Y') }}
            </p>
        </div>
    </div>

    {{-- HP: angka dalam grid 3 kolom selebar kartu supaya tiga angka utama
         tetap satu baris (2026-09-21, feedback user). Desktop: berjajar di kanan. --}}
    <div class="grid w-full grid-cols-3 gap-x-3 gap-y-3 border-t border-gray-100 pt-3 dark:border-gray-700
                sm:ml-auto sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:gap-x-5 sm:gap-y-2 sm:border-0 sm:pt-0">
        @foreach ($stats as $s)
            <div class="min-w-0 sm:min-w-[86px] sm:border-l sm:border-gray-100 sm:pl-4 sm:first:border-0 sm:first:pl-0 dark:border-gray-700" title="{{ $s['tip'] }}">
                <p class="truncate text-[10px] uppercase tracking-wide text-gray-400 sm:text-[11px] dark:text-gray-400">{{ $s['label'] }}</p>
                <p class="text-lg font-bold leading-tight {{ $s['tone'] }}">{{ $s['value'] }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-400">{{ $s['sub'] }}</p>
            </div>
        @endforeach
    </div>
</div>
