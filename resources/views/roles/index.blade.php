@extends('layouts.app')

@section('title', 'Kelola Role & Izin')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    <x-page-header title="Kelola Role & Izin" subtitle='Atur izin "Lihat" dan "Kelola" untuk tiap role, per modul.' />

    <div class="rounded-md bg-blue-50 border border-blue-200 text-blue-800 text-sm px-4 py-3 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300">
        ℹ️ Role <strong>Administrator</strong> tidak ditampilkan di sini karena selalu memiliki akses penuh ke seluruh
        modul secara otomatis — ini untuk mencegah Administrator tidak sengaja mengunci dirinya sendiri dari sistem.
    </div>

    <form action="{{ route('roles.update') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- overflow-x-auto WAJIB di sini supaya tabel lebar ini tetap
             bisa digeser horizontal di layar HP, bukan bikin seluruh
             halaman melebar dan berantakan. --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto dark:bg-gray-800 dark:border-gray-700">
            @include('partials.scroll-hint')
            <table class="min-w-[720px] w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                    <tr>
                        {{-- Kolom Izin menempel di kiri saat tabel digeser di HP
                             (2026-09-20, hasil audit UI) — tanpa ini nama izin
                             hilang begitu kolom role digeser. --}}
                        <th class="sticky left-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">Izin</th>
                        @foreach ($roles as $role)
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase whitespace-nowrap dark:text-gray-400">
                                {{ $role->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($permissionsByGroup as $groupName => $permissions)
                        <tr class="bg-gray-50 dark:bg-gray-900">
                            <td colspan="{{ $roles->count() + 1 }}" class="sticky left-0 bg-gray-50 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                                {{ $groupName }}
                            </td>
                        </tr>
                        @foreach ($permissions as $permission)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/60">
                                <td class="sticky left-0 z-10 min-w-[210px] bg-white px-4 py-3 text-gray-700 group-hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300">{{ $permission->label }}</td>
                                @foreach ($roles as $role)
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox"
                                               name="permissions[{{ $role->id }}][]"
                                               value="{{ $permission->id }}"
                                               class="h-5 w-5 rounded border-gray-300 sm:h-4 sm:w-4 dark:border-gray-600"
                                               @checked($role->permissions->contains('id', $permission->id))>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tombol simpan rata kanan, seragam dengan form lain (2026-09-25). --}}
        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-4 text-sm font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">
                Simpan Perubahan Hak Akses
            </button>
        </div>
    </form>
</div>
@endsection
