@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-5xl mx-auto py-8 space-y-6">

    {{-- Kepala profil: foto (langsung bisa diganti/dihapus) + nama, email,
         role & status. Satu-satunya foto di halaman ini — isian foto ikut
         form Biodata lewat atribut form="profileBioForm" (2026-09-15). --}}
    <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-center sm:text-left">
        @include('partials.avatar-upload', ['avatarUser' => $user, 'avatarForm' => 'profileBioForm', 'avatarSize' => 'lg'])
        <div class="min-w-0 sm:pb-5">
            <h1 class="break-words text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h1>
            <p class="break-all text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            <div class="mt-2 flex flex-wrap justify-center gap-1.5 sm:justify-start">
                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ $user->role->name ?? '-' }}</span>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->is_active ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
                @if ($user->jabatan)
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $user->jabatan }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Tombol simpan tiap kartu berupa ikon di pojok kanan judul — pola sama
         dengan kartu-kartu di halaman detail proposal (2026-09-15, feedback user). --}}

    {{-- ===================== BIODATA ===================== --}}
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileBioForm"
          class="space-y-5 bg-white rounded-lg border border-gray-200 shadow-sm p-6 lift dark:bg-gray-800 dark:border-gray-700">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Biodata</h2>
            <button type="submit" title="Simpan Biodata" aria-label="Simpan Biodata"
                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                @include('partials.icon-check')
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" value="{{ $user->username }}" disabled
                       title="Username hanya bisa diubah Administrator"
                       class="mt-1 w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm dark:border-gray-600 dark:bg-gray-900">
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">Dipakai untuk login. Minta Administrator bila perlu diubah.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            {{-- Password di halaman ini punya kartu sendiri (Keamanan), jadi
                 Nomor WhatsApp ikut blok akun di sebelah Email. --}}
            <div class="sm:col-span-2 lg:col-span-1">
                @include('partials.whatsapp-field', ['waValue' => $user->whatsapp_number])
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
            <button type="submit" title="Simpan Password Baru" aria-label="Simpan Password Baru"
                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                @include('partials.icon-check')
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Saat Ini</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
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
