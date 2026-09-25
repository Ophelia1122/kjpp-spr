{{-- ---------- DAFTAR PETUGAS (bebas jumlah & jabatan — mis. 2 Penilai + 1 Reviewer,
     atau 1 Reviewer + 1 Penilai + 1 Pelaksana Inspeksi) ----------
     Partial ISI SAJA (tanpa wrapper #assignmentStaffContainer — wrapper-nya
     ada di proposals/show.blade.php supaya elemen itu sendiri tidak pernah
     diganti, sehingga event listener AJAX yang nempel di situ tidak lepas
     tiap kali tambah/hapus petugas). --}}
<h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 dark:text-gray-400">
    Petugas <span class="font-normal normal-case text-gray-400">(tabel &quot;Adapun petugas kami&quot;)</span>
</h3>

{{-- Urutan bisa digeser (2026-09-24, feedback user): dulu memperbaiki urutan
     berarti menghapus lalu menambah ulang satu per satu. --}}
<div id="staffList" data-reorder-url="{{ route('projects.assignmentStaff.reorder', $project) }}">
@forelse ($project->assignmentStaff as $staff)
    <div class="staff-row flex items-center justify-between gap-2 py-1.5 text-sm border-b border-gray-50 last:border-b-0 dark:border-gray-700/60"
         data-id="{{ $staff->id }}" @can('assignment_letter.manage') draggable="true" @endcan>
        <div class="flex min-w-0 items-center gap-2">
            @can('assignment_letter.manage')
                <span class="staff-grip cursor-grab text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="Geser untuk mengubah urutan" aria-hidden="true">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/>
                    </svg>
                </span>
            @endcan
            <div class="min-w-0">
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $staff->user->name ?? '(pengguna terhapus)' }}</span>
            <span class="text-gray-400 dark:text-gray-500">
                — {{ $staff->user->jabatan ?? '(jabatan belum diisi)' }}
                @if ($staff->user?->mappi_no) &middot; MAPPI {{ $staff->user->mappi_no }} @endif
            </span>
            </div>
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
</div>

@can('assignment_letter.manage')
    @if ($project->assignmentStaff->count() > 1)
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Geser baris untuk mengubah urutan cetak di Surat Tugas.</p>
    @endif

    <script>
    (function () {
        const daftar = document.getElementById('staffList');
        if (! daftar || daftar.dataset.siap) return;
        daftar.dataset.siap = '1';

        let diseret = null;

        daftar.addEventListener('dragstart', function (e) {
            diseret = e.target.closest('.staff-row');
            if (! diseret) return;
            diseret.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        });

        daftar.addEventListener('dragend', function () {
            if (! diseret) return;
            diseret.style.opacity = '';
            diseret = null;
            simpanUrutan();
        });

        daftar.addEventListener('dragover', function (e) {
            e.preventDefault();
            const target = e.target.closest('.staff-row');
            if (! target || ! diseret || target === diseret) return;

            const kotak = target.getBoundingClientRect();
            const setelah = (e.clientY - kotak.top) > kotak.height / 2;
            daftar.insertBefore(diseret, setelah ? target.nextSibling : target);
        });

        function simpanUrutan() {
            const ids = [...daftar.querySelectorAll('.staff-row')].map(r => r.dataset.id);
            fetch(daftar.dataset.reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ ids }),
            });
        }
    })();
    </script>
@endcan

@can('assignment_letter.manage')
    {{-- Petugas Surat Tugas berisi penilai/pelaksana/reviewer; jabatan Admin
         tidak ditawarkan (2026-09-25, feedback user). --}}
    @php
        $availableStaffUsers = $activeUsers
            ->whereNotIn('id', $project->assignmentStaff->pluck('user_id'))
            ->reject(fn ($u) => $u->jabatan === \App\Models\User::JABATAN_ADMIN);
    @endphp
    @if ($availableStaffUsers->isEmpty())
        {{-- Semua pengguna aktif sudah masuk daftar: kolom pilih disembunyikan
             supaya tidak ada kotak kosong menggantung (2026-09-24, feedback user). --}}
        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">Semua pengguna aktif sudah ada di daftar petugas.</p>
    @else
        {{-- min-w-0 + flex-wrap: nama petugas yang panjang membuat <select> selebar
             teks terpanjang dan melebarkan halaman di HP, sehingga bar tombol bawah
             tidak pas dengan halaman (2026-09-14, feedback user). --}}
        <form action="{{ route('projects.assignmentStaff.store', $project) }}" method="POST" class="flex flex-wrap gap-2 mt-3" id="assignmentStaffAddForm">
            @csrf
            <select name="user_id" required class="min-w-0 flex-1 basis-48 h-[38px] rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600 dark:bg-gray-900">
                <option value="">-- Pilih petugas --</option>
                @foreach ($availableStaffUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->jabatan ?: 'jabatan belum diisi' }})</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-[38px] items-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700 whitespace-nowrap">
                + Tambah
            </button>
        </form>
    @endif
@endcan
