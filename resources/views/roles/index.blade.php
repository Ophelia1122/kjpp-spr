@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-8 space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Kelola Hak Akses (Role Management)</h1>
        <p class="text-sm text-gray-500">Atur izin "Lihat" dan "Kelola" untuk tiap role, per modul.</p>
    </div>

    <div class="rounded-md bg-blue-50 border border-blue-200 text-blue-800 text-sm px-4 py-3">
        ℹ️ Role <strong>Administrator</strong> tidak ditampilkan di sini karena selalu memiliki akses penuh ke seluruh
        modul secara otomatis — ini untuk mencegah Administrator tidak sengaja mengunci dirinya sendiri dari sistem.
    </div>

    <form action="{{ route('roles.update') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- overflow-x-auto WAJIB di sini supaya tabel lebar ini tetap
             bisa digeser horizontal di layar HP, bukan bikin seluruh
             halaman melebar dan berantakan. --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
            <table class="min-w-[720px] w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Izin</th>
                        @foreach ($roles as $role)
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">
                                {{ $role->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($permissionsByGroup as $groupName => $permissions)
                        <tr class="bg-gray-50">
                            <td colspan="{{ $roles->count() + 1 }}" class="px-4 py-2 text-xs font-bold text-gray-600 uppercase tracking-wide">
                                {{ $groupName }}
                            </td>
                        </tr>
                        @foreach ($permissions as $permission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-700">{{ $permission->label }}</td>
                                @foreach ($roles as $role)
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox"
                                               name="permissions[{{ $role->id }}][]"
                                               value="{{ $permission->id }}"
                                               class="rounded border-gray-300 w-4 h-4"
                                               @checked($role->permissions->contains('id', $permission->id))>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                Simpan Perubahan Hak Akses
            </button>
        </div>
    </form>
</div>
@endsection
