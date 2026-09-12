@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Edit Pengguna</h1>
        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Kembali</a>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        @csrf
        @method('PUT')

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

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Baru (opsional)</label>
            <input type="password" name="password" minlength="8"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Kosongkan kalau tidak ingin mengubah password.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
            <select name="role_id" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ===================== BIODATA PROFESI ===================== --}}
        @include('partials.biodata-fieldset', ['bioUser' => $user])

        @if ($user->id !== auth()->id())
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600" @checked(old('is_active', $user->is_active))>
                Akun Aktif (nonaktifkan kalau karyawan resign / cuti panjang)
            </label>
        @else
            <input type="hidden" name="is_active" value="1">
            <p class="text-xs text-gray-400 dark:text-gray-500">Anda tidak dapat menonaktifkan akun sendiri.</p>
        @endif

        <div class="pt-2 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                Simpan Perubahan
            </button>
            <a href="{{ route('users.index') }}" class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700 dark:border-gray-600 dark:hover:bg-gray-700/60 dark:text-gray-300">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
