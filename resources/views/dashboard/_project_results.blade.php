@php
    // ---------- Helper header kolom yang bisa diklik untuk sorting ----------
    // Sorting dilakukan di SQL (lihat DashboardController@index), jadi hasilnya
    // benar untuk SELURUH data, bukan cuma baris di halaman yang sedang dibuka.
    // $currentSort/$currentDir dikirim controller — bukan dibaca dari query
    // string — supaya panah indikator tetap benar saat urutannya datang dari
    // default per-tab ("Proyek Saya" default ke tenggat SLA terdekat).
    $sortUrl = function (string $key) use ($currentSort, $currentDir) {
        // Klik kolom yang sama = balik arah; kolom baru = mulai dari desc.
        $dir = ($currentSort === $key && $currentDir === 'desc') ? 'asc' : 'desc';
        return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $dir, 'page' => null]);
    };

    $slaDotTone = [
        'overdue'  => 'bg-rose-500',
        'due-soon' => 'bg-amber-500',
        'on-track' => 'bg-emerald-500',
        'none'     => 'bg-gray-300 dark:bg-gray-600',
    ];

    // SLA yang sedang berjalan untuk tiap proyek — fase Final kalau hasil
    // penilaian sudah dikonfirmasi, kalau belum ya fase Draft. Logika sama
    // dengan badge SLA di halaman detail proposal.
    $activeSlaFor = function ($project) {
        if (!$project->assigned_appraiser || !$project->survey_date
            || $project->isCancelled() || $project->status === \App\Models\Project::STATUS_SELESAI) {
            return null;
        }
        return ($project->isReviewApproved() && $project->estimated_final_completion_date)
            ? ['state' => $project->final_sla_state, 'text' => $project->final_sla_label, 'phase' => 'Laporan Final', 'target' => $project->estimated_final_completion_date_formatted]
            : ['state' => $project->sla_state, 'text' => $project->sla_label, 'phase' => 'Draft/Resume', 'target' => $project->estimated_completion_date_formatted];
    };
@endphp

{{-- ===================== BARIS RINGKASAN + JUMLAH PER HALAMAN ===================== --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-gray-500 dark:text-gray-400" id="resultsCount">{{ $projects->total() }} proyek ditemukan</p>
    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <span>Tampilkan</span>
        @foreach ([15, 25, 50, 100] as $size)
            <a href="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => null]) }}"
               class="px-2 py-1 rounded-md font-medium {{ (int) request('per_page', 15) === $size ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                {{ $size }}
            </a>
        @endforeach
    </div>
</div>

{{-- ===================== TABEL PROYEK (DESKTOP) ===================== --}}
<div class="hidden lg:block bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift dark:bg-gray-800 dark:border-gray-700">
    {{-- Urutan kolom mengikuti alur baca: siapa → kondisinya → uangnya →
         detail deskriptif (2026-09-14, feedback user). Lebar kolom sempit
         dikunci supaya sisa ruang jatuh ke Pemberi Tugas & Objek yang isinya
         teks panjang; sebelumnya SLA/Status kebagian lebar berlebih sementara
         nama klien terjepit sampai patah jadi 4 baris. --}}
    {{-- Ukuran font isi tabel diseragamkan 11px (2026-09-14, feedback user) —
         sebelumnya campur 14px/12px/11px per kolom sehingga baris terlihat
         tidak rata. Diatur sekali di <table>, sel-selnya tinggal mewarisi. --}}
    <table class="min-w-[1180px] w-full text-[11px]">
        <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
            <tr class="text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                @php
                    $cols = [
                        ['key' => 'proposal_number', 'label' => 'No. Proposal',  'class' => 'px-3 py-3'],
                        ['key' => null,              'label' => 'Pemberi Tugas', 'class' => 'px-3 py-3 min-w-[190px]'],
                        ['key' => 'status',          'label' => 'Status',        'class' => 'px-3 py-3 w-[126px]'],
                        ['key' => 'deadline',        'label' => 'SLA',           'class' => 'px-3 py-3 w-[104px]', 'tip' => 'Urutkan berdasarkan tenggat SLA — yang paling mepet di atas'],
                        ['key' => 'fee',             'label' => 'Fee Jasa',      'class' => 'px-3 py-3 whitespace-nowrap w-[118px]'],
                        ['key' => null,              'label' => 'Sisa Tagihan',  'class' => 'px-3 py-3 whitespace-nowrap w-[118px]'],
                        ['key' => 'purpose',         'label' => 'Jenis',         'class' => 'px-3 py-3 w-[112px]'],
                        ['key' => null,              'label' => 'Objek',         'class' => 'px-3 py-3 min-w-[140px]'],
                        ['key' => null,              'label' => 'Aksi',          'class' => 'px-3 py-3 w-[100px]'],
                    ];
                @endphp
                @foreach ($cols as $col)
                    <th class="{{ $col['class'] }}">
                        @if ($col['key'])
                            <a href="{{ $sortUrl($col['key']) }}" @isset($col['tip']) title="{{ $col['tip'] }}" @endisset
                               class="inline-flex items-center gap-1 hover:text-gray-800 dark:hover:text-gray-200">
                                {{ $col['label'] }}
                                @if ($currentSort === $col['key'])
                                    <svg class="h-3 w-3 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $currentDir === 'asc' ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
                                    </svg>
                                @else
                                    <svg class="h-3 w-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                                    </svg>
                                @endif
                            </a>
                        @else
                            {{ $col['label'] }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($projects as $project)
                @php $sla = $activeSlaFor($project); @endphp
                {{-- Seluruh baris bisa diklik menuju detail (2026-09-14, feedback
                     user) — sebelumnya harus tepat mengenai ikon mata yang kecil.
                     Ikon aksi di kolom terakhir menghentikan propagasi klik. --}}
                <tr data-href="{{ route('proposals.show', $project) }}"
                    class="project-row cursor-pointer hover:bg-blue-50/40 dark:hover:bg-blue-900/20 {{ $project->status === \App\Models\Project::STATUS_BATAL ? 'opacity-60' : '' }}">
                    {{-- Nomor proposal dijaga tetap 1 baris (whitespace-nowrap) —
                         dipatahkan jadi 3-4 baris justru bikin tinggi baris
                         melar dan susah dipindai. Ukuran font dikecilkan supaya
                         nomor terpanjang tetap muat. --}}
                    <td class="px-3 py-3 font-semibold text-gray-900 whitespace-nowrap dark:text-gray-100">
                        {{ $project->proposal_number }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 dark:text-gray-300">{{ $project->instructingClient->client_name ?? '-' }}</td>
                    <td class="px-3 py-3 text-center">
                        <span class="inline-block px-2.5 py-1 rounded-full font-semibold whitespace-nowrap {{ $project->status_badge_classes }}"
                              title="{{ $project->status }}">
                            {{ $project->status_short }}
                        </span>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-center">
                        @if ($sla)
                            <span class="inline-flex items-center gap-1.5 {{ $sla['state'] === 'overdue' ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}"
                                  title="SLA {{ $sla['phase'] }} — target {{ $sla['target'] ?? '—' }}">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $slaDotTone[$sla['state']] ?? $slaDotTone['none'] }}"></span>
                                {{ $sla['text'] }}
                            </span>
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-right text-gray-700 whitespace-nowrap tabular-nums dark:text-gray-300">
                        Rp {{ number_format($project->total_fee, 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-3 text-right whitespace-nowrap tabular-nums">
                        @if ($project->remaining_balance > 0)
                            <span class="font-medium text-amber-600 dark:text-amber-400">Rp {{ number_format($project->remaining_balance, 0, ',', '.') }}</span>
                        @else
                            <span class="font-medium text-emerald-600 dark:text-emerald-400">Lunas</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $project->proposal_purpose }}</td>
                    <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $project->asset_type }}</td>
                    <td class="px-3 py-3">
                        <div class="flex justify-center items-center gap-1.5" data-row-actions>
                            <a href="{{ route('proposals.show', $project) }}" title="Lihat / kelola"
                               class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-blue-100 hover:text-blue-700 dark:text-gray-400 dark:hover:bg-blue-900/40 dark:hover:text-blue-300">
                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </a>
                            @can('proposals.manage')
                                @if ($project->status === \App\Models\Project::STATUS_DRAFT)
                                    <a href="{{ route('proposals.edit', $project) }}" title="Edit proposal"
                                       class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('proposals.destroy', $project) }}" method="POST"
                                          onsubmit="return confirm('Yakin hapus proposal {{ $project->proposal_number }}? Aksi ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus proposal"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                        Belum ada proyek yang cocok. Coba longgarkan filter, atau klik "Buat Proposal Baru".
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ===================== KARTU PROYEK (MOBILE & TABLET) =====================
     Tabelnya butuh lebar ~980px; di layar sempit lebih enak dibaca sebagai
     kartu daripada digeser ke samping terus (2026-09-14, feedback user). --}}
<div class="lg:hidden space-y-3">
    @forelse ($projects as $project)
        @php $sla = $activeSlaFor($project); @endphp
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3 dark:bg-gray-800 dark:border-gray-700 {{ $project->status === \App\Models\Project::STATUS_BATAL ? 'opacity-60' : '' }}">
            <a href="{{ route('proposals.show', $project) }}" class="block">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 break-words dark:text-gray-100">{{ $project->proposal_number }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $project->instructingClient->client_name ?? '-' }}</p>
                    </div>
                    <span class="shrink-0 inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $project->status_badge_classes }}"
                          title="{{ $project->status }}">
                        {{ $project->status_short }}
                    </span>
                </div>

                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $project->proposal_purpose }}@if ($project->asset_type) &middot; {{ $project->asset_type }} @endif
                </p>

                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                    <span class="text-gray-500 dark:text-gray-400">
                        Fee <span class="font-medium tabular-nums text-gray-700 dark:text-gray-300">Rp {{ number_format($project->total_fee, 0, ',', '.') }}</span>
                    </span>
                    @if ($project->remaining_balance > 0)
                        <span class="text-gray-500 dark:text-gray-400">
                            Sisa <span class="font-medium tabular-nums text-amber-600 dark:text-amber-400">Rp {{ number_format($project->remaining_balance, 0, ',', '.') }}</span>
                        </span>
                    @else
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">Lunas</span>
                    @endif
                    @if ($sla)
                        <span class="inline-flex items-center gap-1.5 {{ $sla['state'] === 'overdue' ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $slaDotTone[$sla['state']] ?? $slaDotTone['none'] }}"></span>
                            {{ $sla['text'] }}
                        </span>
                    @endif
                </div>
            </a>

            @can('proposals.manage')
                @if ($project->status === \App\Models\Project::STATUS_DRAFT)
                    <div class="mt-2 flex items-center gap-1.5 border-t border-gray-100 pt-2 dark:border-gray-700">
                        <a href="{{ route('proposals.edit', $project) }}" title="Edit proposal"
                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                            </svg>
                        </a>
                        <form action="{{ route('proposals.destroy', $project) }}" method="POST"
                              onsubmit="return confirm('Yakin hapus proposal {{ $project->proposal_number }}? Aksi ini tidak bisa dibatalkan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Hapus proposal"
                                    class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    @empty
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 text-center text-gray-400 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500">
            Belum ada proyek yang cocok. Coba longgarkan filter, atau klik "Buat Proposal Baru".
        </div>
    @endforelse
</div>

{{-- ===================== PAGINASI ===================== --}}
<div>
    {{ $projects->links() }}
</div>
