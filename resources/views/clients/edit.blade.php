@extends('layouts.app')

@section('title', 'Edit Klien')

@section('content')
<div class="max-w-lg mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Edit Klien</h1>
        <a href="{{ route('clients.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Kembali ke Database Klien</a>
    </div>

    <form action="{{ route('clients.update', $client) }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Klien</label>
            <input type="text" name="client_name" value="{{ old('client_name', $client->client_name) }}" required
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Klien</label>
            <select name="client_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                <option value="Perbankan" @selected($client->client_type === 'Perbankan')>Perbankan</option>
                <option value="Korporat" @selected($client->client_type === 'Korporat')>Korporat</option>
                <option value="Perorangan" @selected($client->client_type === 'Perorangan')>Perorangan</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat</label>
            <textarea name="address" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">{{ old('address', $client->address) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kontak Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">No. Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $client->phone) }}"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input type="email" name="email" value="{{ old('email', $client->email) }}"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                Simpan Perubahan
            </button>
            <a href="{{ route('clients.index') }}" class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700 dark:border-gray-600 dark:hover:bg-gray-700/60 dark:text-gray-300">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
