@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Tambah Pengguna</h1>
        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali</a>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" required minlength="8"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            <p class="mt-1 text-xs text-gray-400">Minimal 8 karakter.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role_id" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <option value="">-- Pilih Role --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                Simpan Pengguna
            </button>
            <a href="{{ route('users.index') }}" class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
