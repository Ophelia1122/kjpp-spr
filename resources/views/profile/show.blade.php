@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-2xl mx-auto py-8 space-y-6">

    <div class="flex items-center gap-4">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-blue-600 text-lg font-semibold text-white uppercase">
            {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }} · {{ $user->role->name ?? '-' }}</p>
        </div>
    </div>

    {{-- Tombol simpan tiap kartu berupa ikon di pojok kanan judul — pola sama
         dengan kartu-kartu di halaman detail proposal (2026-09-15, feedback user). --}}

    {{-- ===================== BIODATA ===================== --}}
    <form action="{{ route('profile.update') }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Biodata</h2>
            <button type="submit" title="Simpan Biodata"
                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                @include('partials.icon-check')
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="block text-gray-500 dark:text-gray-400">Role</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $user->role->name ?? '-' }}</span>
                <span class="block text-xs text-gray-400 dark:text-gray-500">Diatur oleh Administrator.</span>
            </div>
            <div>
                <span class="block text-gray-500 dark:text-gray-400">Status Akun</span>
                <span class="font-medium {{ $user->is_active ? 'text-green-700' : 'text-gray-500' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
        </div>

        @include('partials.biodata-fieldset', ['bioUser' => $user])
    </form>

    {{-- ===================== KEAMANAN ===================== --}}
    <form action="{{ route('profile.updatePassword') }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Keamanan — Ganti Password</h2>
            <button type="submit" title="Simpan Password Baru"
                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                @include('partials.icon-check')
            </button>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Saat Ini</label>
            <input type="password" name="current_password" required autocomplete="current-password"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Baru</label>
                <input type="password" name="password" required minlength="8" autocomplete="new-password"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
        </div>
    </form>
</div>
@endsection
