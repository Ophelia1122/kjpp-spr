@extends('layouts.app')

@section('title', 'List Project')

@section('content')
@php
    // Chip filter aktif — supaya jelas daftar sedang disaring apa, dan tiap
    // filter bisa dibuang satu per satu tanpa mereset semuanya
    // (2026-09-14, feedback user).
    $activeFilters = [];
    if (request('q')) {
        $activeFilters[] = ['key' => 'q', 'label' => 'Cari: "' . request('q') . '"'];
    }
    if (request('status')) {
        $activeFilters[] = ['key' => 'status', 'label' => 'Status: ' . request('status')];
    }
    if (request('purpose')) {
        $activeFilters[] = ['key' => 'purpose', 'label' => 'Jenis: ' . request('purpose')];
    }
    if (request('appraiser')) {
        $appraiserName = optional($appraiserOptions->firstWhere('id', (int) request('appraiser')))->name;
        $activeFilters[] = ['key' => 'appraiser', 'label' => 'Penilai: ' . ($appraiserName ?? request('appraiser'))];
    }
    if (request('from')) {
        $activeFilters[] = ['key' => 'from', 'label' => 'Dari: ' . request('from')];
    }
    if (request('to')) {
        $activeFilters[] = ['key' => 'to', 'label' => 'Sampai: ' . request('to')];
    }
    $focusLabels = ['active' => 'Proyek aktif', 'overdue' => 'Lewat deadline', 'survey_week' => 'Survei minggu ini', 'survey_month' => 'Survei bulan ini'];
    if (isset($focusLabels[request('focus')])) {
        $activeFilters[] = ['key' => 'focus', 'label' => $focusLabels[request('focus')]];
    }
    $hasAdvancedFilter =request('purpose') || request('appraiser') || request('from') || request('to');
@endphp

<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER + AKSI UTAMA ===================== --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            {{-- Judul disamakan dengan label menu sidebar "List Project"
                 (2026-09-14, feedback user) — sebelumnya "Dashboard Project",
                 bertabrakan dengan menu "Dashboard" tepat di atasnya. --}}
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">List Project</h1>
            {{-- Tab "Proyek Saya" disembunyikan untuk role admin (2026-09-13,
                 feedback user) — mereka tidak ditugaskan ke lapangan. Jabatan
                 Reviewer tetap melihatnya (lihat User::seesOfficeWide). --}}
            @unless (auth()->user()->seesOfficeWide())
            <div class="flex gap-2 mt-2">
                <a href="{{ route('dashboard', array_merge(request()->except(['mine', 'page']), ['mine' => 0])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs rounded-full font-medium {{ !$mine ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/>
                    </svg>
                    Semua Proyek
                </a>
                <a href="{{ route('dashboard', array_merge(request()->except(['mine', 'page']), ['mine' => 1])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs rounded-full font-medium {{ $mine ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    Proyek Saya
                </a>
            </div>
            @endunless
        </div>
        </div>
    </div>

    {{-- ===================== FILTER & PENCARIAN ===================== --}}
    <form id="searchForm" method="GET" action="{{ route('dashboard') }}"
          class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 space-y-3 dark:bg-gray-800 dark:border-gray-700">
        <input type="hidden" name="mine" value="{{ $mine ? 1 : 0 }}">
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="dir" value="{{ request('dir') }}">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        <input type="hidden" name="focus" value="{{ request('focus') }}">

        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Cari</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="No. proposal, pemilik aset, atau nama klien..."
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>
            <div class="min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                    <option value="">Semua Status</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="toggleAdvanced"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                </svg>
                Filter Lanjutan
            </button>

            {{-- Export & Proposal Baru pindah ke dalam kartu filter (2026-09-19,
                 feedback user) — satu border dengan Cari, header jadi lega. --}}
            <div class="ml-auto flex items-center gap-2">
                @can('reports.export')
                    <button type="button" onclick="openExportModal()" title="Export ke Excel"
                       class="inline-flex items-center gap-1.5 rounded-md border border-emerald-600 px-2.5 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-300 dark:hover:bg-emerald-900/30">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Excel
                    </button>
                @endcan
                @can('proposals.manage')
                    <a href="{{ route('proposals.create') }}" title="Buat Proposal Baru"
                       class="inline-flex items-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-2.5 py-2 text-xs font-medium text-white hover:border-blue-700 hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Proposal Baru
                    </a>
                @endcan
            </div>
        </div>

        {{-- Filter lanjutan: disembunyikan secara default supaya baris filter
             utama tetap ringkas, tapi otomatis terbuka kalau salah satunya
             sedang dipakai. --}}
        {{-- display diatur lewat style inline, bukan class "hidden" — di build
             Tailwind CDN yang dipakai, .grid menang atas .hidden. --}}
        <div id="advancedFilters" @if (!$hasAdvancedFilter) style="display:none" @endif
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Jenis Proposal</label>
                <select name="purpose" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                    <option value="">Semua Jenis</option>
                    @foreach ($purposeOptions as $purpose)
                        <option value="{{ $purpose }}" @selected(request('purpose') === $purpose)>{{ $purpose }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Penilai Lapangan</label>
                <select name="appraiser" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                    <option value="">Semua Penilai</option>
                    @foreach ($appraiserOptions as $appraiser)
                        {{-- Jabatan (biodata) di samping nama — bukan role akun. --}}
                        <option value="{{ $appraiser->id }}" @selected((int) request('appraiser') === $appraiser->id)>
                            {{ $appraiser->name }}{{ $appraiser->jabatan ? ' — ' . $appraiser->jabatan : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Dibuat Dari</label>
                <input type="date" name="from" value="{{ request('from') }}" lang="id"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Sampai</label>
                <input type="date" name="to" value="{{ request('to') }}" lang="id"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>
        </div>

        {{-- Chip filter aktif — klik × untuk membuang satu filter saja. --}}
        @if (count($activeFilters) > 0)
            <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                <span class="text-xs text-gray-400 dark:text-gray-500">Filter aktif:</span>
                @foreach ($activeFilters as $filter)
                    <a href="{{ route('dashboard', array_merge(request()->except([$filter['key'], 'page']), ['mine' => $mine ? 1 : 0])) }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300">
                        {{ $filter['label'] }}
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endforeach
                <a href="{{ route('dashboard', ['mine' => $mine ? 1 : 0]) }}"
                   class="text-xs text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Reset semua
                </a>
            </div>
        @endif
    </form>

    <div id="searchResults" class="space-y-4">
        @include('dashboard._project_results')
    </div>

</div>

@can('reports.export')
{{-- ===================== MODAL EXPORT EXCEL (2026-09-14, feedback user) ===================== --}}
<div id="exportModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <form id="exportForm" method="GET" action="{{ route('dashboard.exportExcel') }}"
          class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
        <input type="hidden" name="mine" value="{{ $mine ? 1 : 0 }}">

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Export ke Excel</h2>
            <button type="button" onclick="closeExportModal()" class="text-2xl leading-none text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>

        <fieldset class="space-y-2">
            <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">Rentang Waktu</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (['this_month' => 'Bulan ini', 'last_month' => 'Bulan lalu', 'this_year' => 'Tahun ini', 'all' => 'Semua'] as $key => $label)
                    <button type="button" onclick="setExportRange('{{ $key }}')"
                            class="rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">{{ $label }}</button>
                @endforeach
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label for="export_date_field" class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Berdasarkan</label>
                    <select id="export_date_field" name="date_field" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                        <option value="proposal_date">Tanggal Proposal</option>
                        <option value="created_at">Tanggal Dibuat</option>
                        <option value="survey_date">Tanggal Survei</option>
                    </select>
                </div>
                <div>
                    <label for="export_from" class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Dari</label>
                    <input type="date" id="export_from" name="from" value="{{ request('from') }}" lang="id"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                </div>
                <div>
                    <label for="export_to" class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Sampai</label>
                    <input type="date" id="export_to" name="to" value="{{ request('to') }}" lang="id"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                </div>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500">Kosongkan tanggal untuk mengekspor semua waktu.</p>
        </fieldset>

        <fieldset class="grid grid-cols-1 gap-3 border-t border-gray-100 pt-4 sm:grid-cols-2 dark:border-gray-700">
            <legend class="sr-only">Filter</legend>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                    <option value="">Semua Status</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Jenis Proposal</label>
                <select name="purpose" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                    <option value="">Semua Jenis</option>
                    @foreach ($purposeOptions as $purpose)
                        <option value="{{ $purpose }}" @selected(request('purpose') === $purpose)>{{ $purpose }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Penilai Lapangan</label>
                <select name="appraiser" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                    <option value="">Semua Penilai</option>
                    @foreach ($appraiserOptions as $appraiser)
                        <option value="{{ $appraiser->id }}" @selected((int) request('appraiser') === $appraiser->id)>{{ $appraiser->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Kata Kunci</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="No. proposal / nama klien"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
            </div>
        </fieldset>

        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
            <button type="button" onclick="closeExportModal()"
                    class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Batal</button>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Download Excel
            </button>
        </div>
    </form>
</div>
@endcan

@push('scripts')
    <script>
        initLiveSearch({ form: '#searchForm', results: '#searchResults' });

        // ---------- Modal Export Excel ----------
        function openExportModal() {
            const m = document.getElementById('exportModal');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }
        function closeExportModal() {
            const m = document.getElementById('exportModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }
        function setExportRange(kind) {
            const pad = (n) => String(n).padStart(2, '0');
            const fmt = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            const now = new Date();
            let from = '', to = '';
            if (kind === 'this_month') {
                from = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
                to = fmt(new Date(now.getFullYear(), now.getMonth() + 1, 0));
            } else if (kind === 'last_month') {
                from = fmt(new Date(now.getFullYear(), now.getMonth() - 1, 1));
                to = fmt(new Date(now.getFullYear(), now.getMonth(), 0));
            } else if (kind === 'this_year') {
                from = now.getFullYear() + '-01-01';
                to = now.getFullYear() + '-12-31';
            }
            document.getElementById('export_from').value = from;
            document.getElementById('export_to').value = to;
        }
        document.getElementById('exportForm')?.addEventListener('submit', () => setTimeout(closeExportModal, 300));

        // Klik di mana saja pada baris tabel = buka detail proyek. Klik pada
        // kolom aksi (ikon edit/hapus) dikecualikan supaya tidak saling rebut.
        // Pakai delegasi di document karena isi #searchResults diganti total
        // oleh live search — listener yang dipasang per baris akan hilang.
        document.addEventListener('click', function (e) {
            const row = e.target.closest('.project-row');
            if (!row || e.target.closest('[data-row-actions]')) return;
            window.location = row.dataset.href;
        });

        document.getElementById('toggleAdvanced')?.addEventListener('click', function () {
            const box = document.getElementById('advancedFilters');
            if (box) box.style.display = box.style.display === 'none' ? '' : 'none';
        });
    </script>
@endpush
@endsection
