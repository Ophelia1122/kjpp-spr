@extends('layouts.app')

@section('title', 'Edit Rekening Bank')

@section('content')
<div class="max-w-lg mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Edit Rekening Bank</h1>
        <a href="{{ route('banks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Kembali</a>
    </div>

    <form action="{{ route('banks.update', $bank) }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        @csrf
        @method('PUT')
        @include('banks._form')

        <div class="pt-2 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">Simpan Perubahan</button>
            <a href="{{ route('banks.index') }}" class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700 dark:border-gray-600 dark:hover:bg-gray-700/60 dark:text-gray-300">Batal</a>
        </div>
    </form>
</div>
@endsection
