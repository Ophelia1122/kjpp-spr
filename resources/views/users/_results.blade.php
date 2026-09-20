    {{-- Tabel untuk layar lebar; layar sempit memakai kartu di bawah
         (2026-09-20, hasil audit UI). --}}
    <div class="hidden overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm md:block dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-[640px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">
                    <th class="px-4 py-3">@include('partials.sort-link', ['key' => 'name', 'label' => 'Nama'])</th>
                    <th class="px-4 py-3">Email</th>
                    {{-- Keterangan singkat supaya Role vs Jabatan tidak tertukar (2026-09-14). --}}
                    <th class="px-4 py-3">@include('partials.sort-link', ['key' => 'role', 'label' => 'Role']) <span class="block text-[10px] font-normal normal-case tracking-normal text-gray-500 dark:text-gray-400">menu yang bisa dibuka</span></th>
                    <th class="px-4 py-3">Jabatan <span class="block text-[10px] font-normal normal-case tracking-normal text-gray-500 dark:text-gray-400">tugas &amp; tanda tangan</span></th>
                    <th class="px-4 py-3 whitespace-nowrap">@include('partials.sort-link', ['key' => 'last_login_at', 'label' => 'Login Terakhir'])</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="text-xs text-gray-500 dark:text-gray-400">(Anda)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-500">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-500">{{ $user->role->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-500">{{ $user->jabatan ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap dark:text-gray-500"
                            title="{{ $user->last_login_at?->translatedFormat('d F Y, H:i') }}">
                            {{ $user->last_login_at ? $user->last_login_at->locale('id')->diffForHumans() : 'Belum pernah' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($user->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center items-center gap-1.5">
                                <a href="{{ route('users.edit', $user) }}" title="Edit pengguna" aria-label="Edit pengguna"
                                   class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                    </svg>
                                </a>
                                @if ($user->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                          data-confirm="Yakin hapus pengguna {{ $user->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus pengguna" aria-label="Hapus pengguna"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-500 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="grid h-8 w-8 place-items-center rounded-md text-gray-200" title="Tidak bisa menghapus akun sendiri">
                                        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada pengguna yang cocok</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Coba kata kunci lain, atau tambahkan pengguna baru.</p>
                            <x-btn :href="route('users.create')" class="mt-4">Tambah Pengguna</x-btn>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ---------- KARTU (HP) ---------- --}}
    <div class="space-y-3 md:hidden">
        @forelse ($users as $user)
            <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start gap-3">
                    @include('partials.user-avatar', ['avatarUser' => $user, 'avatarClass' => 'h-10 w-10 bg-blue-600 text-sm'])
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 truncate font-semibold text-gray-900 dark:text-gray-100">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Anda)</span>
                                @endif
                            </p>
                            @if ($user->is_active)
                                <span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700 dark:bg-green-900/30 dark:text-green-400">Aktif</span>
                            @else
                                <span class="shrink-0 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                            @endif
                        </div>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>

                        <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Role</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-300">{{ $user->role->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Jabatan</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-300">{{ $user->jabatan ?: '-' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                Login {{ $user->last_login_at ? $user->last_login_at->locale('id')->diffForHumans() : 'belum pernah' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <a href="{{ route('users.edit', $user) }}" title="Edit pengguna" aria-label="Edit pengguna"
                                   class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                    @include('partials.icon-pencil')
                                </a>
                                @if ($user->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                          data-confirm="Yakin hapus pengguna {{ $user->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus pengguna" aria-label="Hapus pengguna"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            @include('partials.icon-trash')
                                        </button>
                                    </form>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada pengguna yang cocok</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Coba kata kunci lain, atau tambahkan pengguna baru.</p>
                <x-btn :href="route('users.create')" class="mt-4">Tambah Pengguna</x-btn>
            </div>
        @endforelse
    </div>

    <div>{{ $users->links() }}</div>
