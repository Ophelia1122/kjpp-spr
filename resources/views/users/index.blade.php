@extends('layouts.app')

@section('title', 'Kelola Pengguna')
 
@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">
 
    <x-page-header title="Kelola Pengguna" subtitle="Akun, role, dan biodata profesi yang dipakai di proposal &amp; Surat Tugas.">
        <a href="{{ route('users.create') }}" title="Tambah pengguna" aria-label="Tambah pengguna"
           class="inline-flex items-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-2.5 py-1.5 text-xs font-medium text-white hover:border-blue-700 hover:bg-blue-700">
            <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Tambah Pengguna
        </a>
    </x-page-header>

    <form id="searchForm" method="GET" action="{{ route('users.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex gap-3 dark:bg-gray-800 dark:border-gray-700">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..."
               class="flex-1 rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="dir" value="{{ request('dir') }}">
    </form>

    <div id="searchResults" class="space-y-6">
        @include('users._results')
    </div>
</div>

@push('scripts')
    <script>
        initLiveSearch({ form: '#searchForm', results: '#searchResults' });
    </script>
@endpush
@endsection