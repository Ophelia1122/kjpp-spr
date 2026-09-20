@extends('layouts.app')

@section('title', 'Kelola Pengguna')
 
@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">
 
    <x-page-header title="Kelola Pengguna" subtitle="{{ $users->total() }} pengguna terdaftar — akun, role, dan biodata profesi yang dipakai di proposal &amp; Surat Tugas.">
        {{-- Kotak cari ikut kartu header (2026-09-20) — halaman ini pendek,
             tiga blok bertumpuk terasa boros. --}}
        <form id="searchForm" method="GET" action="{{ route('users.index') }}" class="w-full sm:w-64">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..."
                   class="h-[34px] w-full rounded-md border-gray-300 py-0 text-sm shadow-sm dark:border-gray-600">
        </form>
        <a href="{{ route('users.create') }}" title="Tambah pengguna" aria-label="Tambah pengguna"
           class="inline-flex h-[34px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-3 text-xs font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">
            <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Tambah Pengguna
        </a>
    </x-page-header>


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