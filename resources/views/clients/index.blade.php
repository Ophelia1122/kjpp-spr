@extends('layouts.app')

@section('title', 'Database Klien')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <x-page-header title="Database Klien" subtitle="{{ $clients->total() }} klien terdaftar — dipakai sebagai pemberi tugas, pengguna laporan &amp; pihak menyetujui.">
        @can('clients.manage')
            <a href="{{ route('clients.create') }}" title="Tambah klien baru" aria-label="Tambah klien baru"
               class="inline-flex h-[34px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-3 text-xs font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">
                <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Klien Baru
            </a>
        @endcan
        <form id="searchForm" method="GET" action="{{ route('clients.index') }}" class="flex w-full items-center gap-2 sm:w-80">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau alamat klien..."
                   class="h-[34px] w-full rounded-md border-gray-300 py-0 text-sm shadow-sm dark:border-gray-600">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="dir" value="{{ request('dir') }}">
            @if (request('q'))
                <a href="{{ route('clients.index') }}"
                   class="inline-flex h-[34px] shrink-0 items-center rounded-md border border-gray-300 px-3 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Reset</a>
            @endif
        </form>
    </x-page-header>


    <div id="searchResults" class="space-y-6">
        @include('clients._results')
    </div>
</div>

@push('scripts')
    <script>
        initLiveSearch({ form: '#searchForm', results: '#searchResults' });
    </script>
@endpush
@endsection