@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto py-16 text-center">
    <div class="text-6xl mb-4">🔒</div>
    <h1 class="text-2xl font-bold text-gray-900 mb-2 dark:text-gray-100">Akses Ditolak</h1>
    <p class="text-gray-600 mb-6 dark:text-gray-400">
        {{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini.' }}
    </p>

    @auth
        <p class="text-sm text-gray-400 mb-6 dark:text-gray-400">
            Anda login sebagai <strong>{{ auth()->user()->name }}</strong>
            dengan role <strong>{{ auth()->user()->role->name ?? '-' }}</strong>.
            Kalau menurut Anda ini seharusnya bisa diakses, hubungi Administrator
            untuk meninjau ulang hak akses role Anda.
        </p>
    @endauth

    <a href="{{ route('home') }}"
       class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
        &larr; Kembali ke Beranda
    </a>
</div>
@endsection
