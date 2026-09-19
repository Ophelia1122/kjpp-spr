@extends('layouts.app')

@section('title', 'Database Klien')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Database Klien</h1>
        @can('clients.manage')
            {{-- Ukuran disamakan dengan "Proposal Baru" di List Project (2026-09-19). --}}
            <a href="{{ route('clients.create') }}" title="Tambah klien baru"
               class="inline-flex items-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-2.5 py-1.5 text-xs font-medium text-white hover:border-blue-700 hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Klien Baru
            </a>
        @endcan
    </div>

    <form id="searchForm" method="GET" action="{{ route('clients.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex gap-3 dark:bg-gray-800 dark:border-gray-700">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau alamat klien..."
               class="flex-1 rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="dir" value="{{ request('dir') }}">
        @if (request('q'))
            <a href="{{ route('clients.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Reset</a>
        @endif
    </form>

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