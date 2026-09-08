@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto py-8">
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Ganti Password Saya</h1>

    <form action="{{ route('profile.updatePassword') }}" method="POST" class="space-y-4 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
            <input type="password" name="current_password" required
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Password Baru</label>
            <input type="password" name="password" required minlength="8"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation" required minlength="8"
                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <button type="submit" class="w-full py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
            Simpan Password Baru
        </button>
    </form>
</div>
@endsection
