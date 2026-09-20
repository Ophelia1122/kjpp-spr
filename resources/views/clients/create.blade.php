@extends('layouts.app')

@section('title', 'Klien Baru')

@section('content')
<div class="max-w-lg mx-auto py-8">
    <h1 class="mb-6 text-xl font-semibold text-gray-900 dark:text-gray-100">Klien Baru</h1>

    <form action="{{ route('clients.store') }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Klien</label>
            <input type="text" name="client_name" value="{{ old('client_name') }}" required autofocus
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            @error('client_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Klien</label>
            <select name="client_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                @foreach (['Perbankan', 'Korporat', 'Perorangan'] as $type)
                    <option value="{{ $type }}" @selected(old('client_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat</label>
            <textarea name="address" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">{{ old('address') }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kontak Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">No. Telepon</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-4 text-sm font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">
                Simpan Klien
            </button>
            <a href="{{ route('clients.index') }}" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
