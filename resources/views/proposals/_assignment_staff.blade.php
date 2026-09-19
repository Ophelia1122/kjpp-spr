{{-- ---------- DAFTAR PETUGAS (bebas jumlah & jabatan — mis. 2 Penilai + 1 Reviewer,
     atau 1 Reviewer + 1 Penilai + 1 Pelaksana Inspeksi) ----------
     Partial ISI SAJA (tanpa wrapper #assignmentStaffContainer — wrapper-nya
     ada di proposals/show.blade.php supaya elemen itu sendiri tidak pernah
     diganti, sehingga event listener AJAX yang nempel di situ tidak lepas
     tiap kali tambah/hapus petugas). --}}
<h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 dark:text-gray-400">
    Petugas <span class="font-normal normal-case text-gray-400">(tabel &quot;Adapun petugas kami&quot;)</span>
</h3>

@forelse ($project->assignmentStaff as $staff)
    <div class="flex items-center justify-between gap-2 py-1.5 text-sm border-b border-gray-50 last:border-b-0 dark:border-gray-700/60">
        <div>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $staff->user->name ?? '(pengguna terhapus)' }}</span>
            <span class="text-gray-400 dark:text-gray-500">
                — {{ $staff->user->jabatan ?? '(jabatan belum diisi)' }}
                @if ($staff->user?->mappi_no) &middot; MAPPI {{ $staff->user->mappi_no }} @endif
            </span>
        </div>
        @can('assignment_letter.manage')
            <form action="{{ route('projects.assignmentStaff.destroy', [$project, $staff]) }}" method="POST"
                  class="assignment-staff-remove-form"
                  data-confirm="Hapus {{ $staff->user->name ?? 'petugas ini' }} dari daftar petugas?">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
            </form>
        @endcan
    </div>
@empty
    <p class="text-xs text-gray-400 dark:text-gray-500">Belum ada petugas ditambahkan.</p>
@endforelse

@can('assignment_letter.manage')
    @php $availableStaffUsers = $activeUsers->whereNotIn('id', $project->assignmentStaff->pluck('user_id')); @endphp
    @if ($availableStaffUsers->count())
        {{-- min-w-0 + flex-wrap: nama petugas yang panjang membuat <select> selebar
             teks terpanjang dan melebarkan halaman di HP, sehingga bar tombol bawah
             tidak pas dengan halaman (2026-09-14, feedback user). --}}
        <form action="{{ route('projects.assignmentStaff.store', $project) }}" method="POST" class="flex flex-wrap gap-2 mt-3" id="assignmentStaffAddForm">
            @csrf
            <select name="user_id" required class="min-w-0 flex-1 basis-48 rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                <option value="">-- Pilih petugas --</option>
                @foreach ($availableStaffUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->jabatan ?: 'jabatan belum diisi' }})</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">
                + Tambah
            </button>
        </form>
    @endif
@endcan
