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
        // SLA berjalan selama pekerjaan aktif (In-Progress s/d Pengiriman) — 2026-09-23.
        if (!$project->assigned_appraiser || !$project->survey_date || !$project->isWorkActive()) {
            return null;
        }
        return ($project->isReviewApproved() && $project->estimated_final_completion_date)
            ? ['state' => $project->final_sla_state, 'text' => $project->final_sla_label, 'phase' => 'Laporan Final', 'target' => $project->estimated_final_completion_date_formatted]
            : ['state' => $project->sla_state, 'text' => $project->sla_label, 'phase' => 'Draft/Resume', 'target' => $project->estimated_completion_date_formatted];
    };
@endphp

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
    {{-- min-w diturunkan 1180 -> 1040px (2026-09-13): nomor proposal kini
         diringkas, sehingga tabel muat di layar laptop tanpa geser samping. --}}
    {{-- Font dinaikkan 11 -> 12px (2026-09-13, feedback user, uji coba). --}}
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-3 py-2 dark:border-gray-700">
            {{-- Jumlah hasil tampil di caption header; nilai ini dipakai live search. --}}
            <span id="resultsCount" hidden>{{ $projects->total() }}</span>

            {{-- Mode pilih-banyak MATI secara bawaan: selama mati, kolom centang
                 tidak dirender sama sekali sehingga tampilan persis seperti
                 sebelumnya (2026-09-24, feedback user). --}}
            <button type="button" id="bulkToggle" aria-pressed="false"
                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                <svg aria-hidden="true" class="h-[15px] w-[15px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span id="bulkToggleLabel">Pilih</span>
            </button>

            @include('partials.per-page', ['paginator' => $projects])
        </div>
    {{-- Isi tabel 13px (2026-09-20, hasil audit UI) — 12px terasa kecil di laptop. --}}
    <table class="min-w-[1040px] w-full text-[13px]">
        <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
            <tr class="text-center text-[12px] font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">
                <th class="bulk-col hidden px-3 py-3 w-[36px]">
                    <input type="checkbox" id="bulkAll" aria-label="Pilih semua baris di halaman ini"
                           class="rounded border-gray-300 dark:border-gray-600">
                </th>
                @php
                    $cols = [
                        ['key' => 'proposal_number', 'label' => 'No. Proposal',  'class' => 'px-3 py-3'],
                        ['key' => null,              'label' => 'Nama Klien',    'class' => 'px-3 py-3 w-full min-w-[240px]'],
                        ['key' => 'purpose',         'label' => 'Jenis',         'class' => 'px-3 py-3 w-[112px]'],
                        ['key' => null,              'label' => 'Objek & Alamat', 'class' => 'px-3 py-3 w-[300px]'],
                        ['key' => 'status',          'label' => 'Status',        'class' => 'px-3 py-3 w-[126px]'],
                        ['key' => 'deadline',        'label' => 'SLA',           'class' => 'px-3 py-3 w-[104px]', 'tip' => 'Urutkan berdasarkan tenggat SLA — yang paling mepet di atas'],
                        ['key' => null,              'label' => 'Aksi',          'class' => 'px-3 py-3 w-[100px]'],
                    ];
                @endphp
                @foreach ($cols as $col)
                    <th class="{{ $col['class'] }}">
                        @if ($col['key'])
                            <a href="{{ $sortUrl($col['key']) }}" @isset($col['tip']) title="{{ $col['tip'] }}" aria-label="{{ $col['tip'] }}" @endisset
                               class="inline-flex items-center gap-1 hover:text-gray-800 dark:hover:text-gray-200">
                                {{ $col['label'] }}
                                @if ($currentSort === $col['key'])
                                    <svg aria-hidden="true" class="h-3 w-3 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $currentDir === 'asc' ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
                                    </svg>
                                @else
                                    <svg aria-hidden="true" class="h-3 w-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
                @php
                    $sla       = $activeSlaFor($project);
                    $obj       = $project->object_summary;
                    $locations = $project->valuationObjects->pluck('location')->filter()->values();
                    $firstAddr = $locations->first() ?? $project->asset_address;
                @endphp
                {{-- Seluruh baris bisa diklik menuju detail (2026-09-14, feedback
                     user) — sebelumnya harus tepat mengenai ikon mata yang kecil.
                     Ikon aksi di kolom terakhir menghentikan propagasi klik. --}}
                <tr data-href="{{ route('proposals.show', $project) }}"
                    class="project-row group cursor-pointer hover:bg-blue-50/40 dark:hover:bg-blue-900/20 {{ $project->status === \App\Models\Project::STATUS_BATAL ? 'opacity-60' : '' }}">
                    <td class="bulk-col hidden px-3 py-3" onclick="event.stopPropagation()">
                        <input type="checkbox" class="bulk-row rounded border-gray-300 dark:border-gray-600"
                               value="{{ $project->id }}" aria-label="Pilih {{ $project->proposal_number }}">
                    </td>
                    {{-- Nomor proposal 1 baris & diringkas (5 karakter awal …
                         bulan/tahun) supaya tabel tidak perlu digeser ke samping.
                         Nomor lengkap muncul sebagai tooltip. --}}
                    <td class="px-3 py-3 font-semibold text-gray-900 whitespace-nowrap dark:text-gray-100"
                        title="{{ $project->proposal_number }}">
                        {{ $project->proposal_number_short }}
                    </td>
                    {{-- Nama Klien, bukan Pemberi Tugas (2026-09-14, feedback user). --}}
                    <td class="px-3 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $project->effective_client_name ?: '-' }}</td>
                    <td class="px-3 py-3 text-gray-500 dark:text-gray-500">{{ $project->proposal_purpose }}</td>
                    {{-- Objek & alamat jadi satu kolom dua baris (2026-09-25, feedback
                         user): jenis objek tetap terbaca penuh, alamat jadi baris
                         kedua yang lebih redup dan dipotong satu baris. --}}
                    <td class="px-3 py-3 text-gray-500 dark:text-gray-500" title="{{ $obj['full'] }}&#10;{{ $locations->implode(' | ') ?: $firstAddr }}">
                        <span class="block max-w-[280px] truncate font-medium text-gray-700 dark:text-gray-300">{{ $obj['short'] }}</span>
                        <span class="block max-w-[280px] truncate text-[11px] text-gray-400 dark:text-gray-500">{{ $firstAddr ?: '—' }}</span>
                        @if ($locations->count() > 1)
                            {{-- Baris sendiri supaya tidak ikut terpotong bersama alamat
                                 (2026-09-25, feedback user). --}}
                            <span class="block text-[11px] text-blue-600 dark:text-blue-400">+{{ $locations->count() - 1 }} lokasi lain</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-center">
                        <span class="inline-block px-2.5 py-1 rounded-full font-semibold whitespace-nowrap {{ $project->status_badge_classes }}"
                              title="{{ $project->status }}">
                            {{ $project->status_short }}
                        </span>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-center">
                        @if ($sla)
                            <span class="inline-flex items-center gap-1.5 {{ $sla['state'] === 'overdue' ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-500' }}"
                                  title="SLA {{ $sla['phase'] }} — target {{ $sla['target'] ?? '—' }}">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $slaDotTone[$sla['state']] ?? $slaDotTone['none'] }}"></span>
                                {{ $sla['text'] }}
                            </span>
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex justify-center items-center gap-1.5" data-row-actions>
                            {{-- Aksi cepat (2026-09-25, feedback user): muncul saat kursor
                                 di atas baris supaya pekerjaan harian tidak perlu membuka
                                 proyek dulu. Di layar sentuh selalu terlihat. --}}
                            @php
                                $tagihanBelumLunas = $project->invoices->where('status', \App\Models\Invoice::STATUS_UNPAID)->values();
                            @endphp
                            @can('invoices.manage')
                                @if ($tagihanBelumLunas->count() === 1)
                                    <form action="{{ route('invoices.markAsPaid', $tagihanBelumLunas[0]) }}" method="POST"
                                          class="opacity-0 transition group-hover:opacity-100 focus-within:opacity-100"
                                          data-confirm="Tandai invoice {{ $tagihanBelumLunas[0]->invoice_number }} sebesar Rp {{ number_format($tagihanBelumLunas[0]->amount, 0, ',', '.') }} sudah LUNAS hari ini?">
                                        @csrf
                                        <button type="submit" title="Tandai lunas" aria-label="Tandai lunas"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-emerald-100 hover:text-emerald-700 dark:text-gray-500 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300">
                                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                            <a href="{{ route('proposals.exportPdf', $project) }}" title="Unduh proposal PDF" aria-label="Unduh proposal PDF"
                               class="grid h-8 w-8 place-items-center rounded-md text-gray-500 opacity-0 transition hover:bg-gray-100 hover:text-gray-900 group-hover:opacity-100 focus:opacity-100 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                            </a>
                            <a href="{{ route('proposals.show', $project) }}" title="Lihat / kelola" aria-label="Lihat / kelola"
                               class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-blue-100 hover:text-blue-700 dark:text-gray-500 dark:hover:bg-blue-900/40 dark:hover:text-blue-300">
                                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </a>
                            @can('proposals.manage')
                                @if ($project->status === \App\Models\Project::STATUS_DRAFT)
                                    <a href="{{ route('proposals.edit', $project) }}" title="Edit proposal" aria-label="Edit proposal"
                                       class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </a>
                                @endif
                                {{-- Proyek Batal bisa dihapus permanen (2026-09-14, feedback user). --}}
                                @if (in_array($project->status, [\App\Models\Project::STATUS_DRAFT, \App\Models\Project::STATUS_BATAL], true))
                                    <form action="{{ route('proposals.destroy', $project) }}" method="POST"
                                          data-confirm="{{ $project->status === \App\Models\Project::STATUS_BATAL ? 'Hapus permanen proyek batal ' . $project->proposal_number . ' beserta seluruh invoice-nya? Aksi ini tidak bisa dibatalkan.' : 'Yakin hapus proposal ' . $project->proposal_number . '? Aksi ini tidak bisa dibatalkan.' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus proposal" aria-label="Hapus proposal"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-500 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
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
                    <td colspan="9" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Belum ada proyek yang cocok</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Longgarkan filter atau pencarian, atau buat proposal baru.</p>
                        @can('proposals.manage')
                            <x-btn :href="route('proposals.create')" class="mt-4">Buat Proposal Baru</x-btn>
                        @endcan
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
    <div class="flex items-center justify-end gap-2 px-1 text-xs text-gray-500 dark:text-gray-500">
        @include('partials.per-page', ['paginator' => $projects])
    </div>
    @forelse ($projects as $project)
        @php
                    $sla       = $activeSlaFor($project);
                    $obj       = $project->object_summary;
                    $locations = $project->valuationObjects->pluck('location')->filter()->values();
                    $firstAddr = $locations->first() ?? $project->asset_address;
                @endphp
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3 dark:bg-gray-800 dark:border-gray-700 {{ $project->status === \App\Models\Project::STATUS_BATAL ? 'opacity-60' : '' }}">
            <a href="{{ route('proposals.show', $project) }}" class="block">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        {{-- Kartu HP disamakan dengan tabel desktop (2026-09-13, feedback user). --}}
                        <p class="font-semibold text-gray-900 dark:text-gray-100" title="{{ $project->proposal_number }}">{{ $project->proposal_number_short }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-500">{{ $project->effective_client_name ?: '-' }}</p>
                    </div>
                    <span class="shrink-0 inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $project->status_badge_classes }}"
                          title="{{ $project->status }}">
                        {{ $project->status_short }}
                    </span>
                </div>

                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-500">
                    {{ $project->proposal_purpose }} &middot; {{ $obj['short'] }}
                </p>
                <p class="mt-1 text-xs text-gray-500 line-clamp-2 dark:text-gray-500">
                    📍 {{ $firstAddr ?: '—' }}
                    @if ($locations->count() > 1)
                        <span class="text-blue-600 dark:text-blue-400">+{{ $locations->count() - 1 }} objek lain</span>
                    @endif
                </p>

                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                    @if ($sla)
                        <span class="inline-flex items-center gap-1.5 {{ $sla['state'] === 'overdue' ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-500' }}">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $slaDotTone[$sla['state']] ?? $slaDotTone['none'] }}"></span>
                            {{ $sla['text'] }}
                        </span>
                    @endif
                </div>
            </a>

            @can('proposals.manage')
                @if (in_array($project->status, [\App\Models\Project::STATUS_DRAFT, \App\Models\Project::STATUS_BATAL], true))
                    <div class="mt-2 flex items-center gap-1.5 border-t border-gray-100 pt-2 dark:border-gray-700">
                        @if ($project->status === \App\Models\Project::STATUS_DRAFT)
                        <a href="{{ route('proposals.edit', $project) }}" title="Edit proposal" aria-label="Edit proposal"
                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                            </svg>
                        </a>
                        @endif
                        <form action="{{ route('proposals.destroy', $project) }}" method="POST"
                              data-confirm="{{ $project->status === \App\Models\Project::STATUS_BATAL ? 'Hapus permanen proyek batal ' . $project->proposal_number . ' beserta seluruh invoice-nya? Aksi ini tidak bisa dibatalkan.' : 'Yakin hapus proposal ' . $project->proposal_number . '? Aksi ini tidak bisa dibatalkan.' }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Hapus proposal" aria-label="Hapus proposal"
                                    class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-500 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    @empty
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 text-center text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Belum ada proyek yang cocok</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Longgarkan filter atau pencarian, atau buat proposal baru.</p>
            @can('proposals.manage')
                <x-btn :href="route('proposals.create')" class="mt-4">Buat Proposal Baru</x-btn>
            @endcan
        </div>
    @endforelse
</div>

{{-- ===================== PAGINASI ===================== --}}
<div>
    {{ $projects->links() }}
</div>

{{-- Bilah aksi untuk baris terpilih; hanya muncul saat mode pilih menyala. --}}
<div id="bulkBar" class="fixed inset-x-0 bottom-4 z-40 hidden justify-center px-4">
    <div class="flex items-center gap-3 rounded-full border border-gray-200 bg-white px-4 py-2 shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <span id="bulkCount" class="text-sm font-medium text-gray-700 dark:text-gray-200">0 dipilih</span>
        <button type="button" id="bulkExport" title="Export baris terpilih ke Excel" aria-label="Export baris terpilih ke Excel"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
            <svg aria-hidden="true" class="h-[16px] w-[16px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
        </button>
        @can('proposals.manage')
            <form id="bulkCancelForm" action="{{ route('proposals.cancelMany') }}" method="POST">
                @csrf
                <button type="submit" id="bulkCancel"
                        class="inline-flex items-center gap-1.5 rounded-md border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-rose-900/30">
                    <svg aria-hidden="true" class="h-[15px] w-[15px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Batalkan
                </button>
            </form>
        @endcan
        <button type="button" id="bulkClear" class="text-xs font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">Bersihkan</button>
    </div>
</div>

<script>
(function () {
    const toggle = document.getElementById('bulkToggle');
    if (! toggle || toggle.dataset.siap) return;     // live search merender ulang partial ini
    toggle.dataset.siap = '1';

    const label   = document.getElementById('bulkToggleLabel');
    const bar     = document.getElementById('bulkBar');
    const hitung  = document.getElementById('bulkCount');
    const semua   = document.getElementById('bulkAll');
    const EXPORT  = @json(route('dashboard.exportExcel'));
    const QUERY   = @json(request()->except(['page', 'ids']));
    let aktif = false;

    const baris = () => [...document.querySelectorAll('.bulk-row')];
    const terpilih = () => baris().filter(c => c.checked).map(c => c.value);

    function gambar() {
        document.querySelectorAll('.bulk-col').forEach(el => el.classList.toggle('hidden', ! aktif));
        label.textContent = aktif ? 'Selesai' : 'Pilih';
        toggle.setAttribute('aria-pressed', aktif ? 'true' : 'false');
        perbaruiJumlah();
    }

    function perbaruiJumlah() {
        const n = terpilih().length;
        hitung.textContent = n + ' dipilih';
        bar.classList.toggle('hidden', ! aktif || n === 0);
        bar.classList.toggle('flex', aktif && n > 0);
        if (semua) semua.checked = n > 0 && n === baris().length;
    }

    toggle.addEventListener('click', function () {
        aktif = ! aktif;
        if (! aktif) baris().forEach(c => { c.checked = false; });
        gambar();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('bulk-row')) perbaruiJumlah();
        if (e.target === semua) {
            baris().forEach(c => { c.checked = semua.checked; });
            perbaruiJumlah();
        }
    });

    document.getElementById('bulkClear').addEventListener('click', function () {
        baris().forEach(c => { c.checked = false; });
        perbaruiJumlah();
    });

    const formBatal = document.getElementById('bulkCancelForm');
    if (formBatal) {
        formBatal.addEventListener('submit', function (e) {
            const ids = terpilih();
            if (! ids.length) { e.preventDefault(); return; }
            if (! confirm('Batalkan ' + ids.length + ' proyek terpilih? Status lamanya disimpan, jadi bisa diaktifkan kembali lewat menu Sampah.')) {
                e.preventDefault();
                return;
            }
            // Kirim id terpilih sebagai input tersembunyi.
            formBatal.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            ids.forEach(function (id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                formBatal.appendChild(input);
            });
        });
    }

    document.getElementById('bulkExport').addEventListener('click', function () {
        const ids = terpilih();
        if (! ids.length) return;
        const url = new URL(EXPORT, window.location.origin);
        Object.entries(QUERY).forEach(([k, v]) => url.searchParams.set(k, v));
        ids.forEach(id => url.searchParams.append('ids[]', id));
        window.location.href = url.toString();
    });

    gambar();
})();
</script>
