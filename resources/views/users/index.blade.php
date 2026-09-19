@extends('layouts.app')

@section('title', 'Kelola Pengguna')
 
@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">
 
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Kelola Pengguna</h1>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
            + Tambah Pengguna
        </a>
    </div>

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