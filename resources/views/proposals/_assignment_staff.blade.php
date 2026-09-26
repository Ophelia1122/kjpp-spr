{{-- ---------- DAFTAR PETUGAS (bebas jumlah & jabatan — mis. 2 Penilai + 1 Reviewer,
     atau 1 Reviewer + 1 Penilai + 1 Pelaksana Inspeksi) ----------
     Partial ISI SAJA (tanpa wrapper #assignmentStaffContainer — wrapper-nya
     ada di proposals/show.blade.php supaya elemen itu sendiri tidak pernah
     diganti, sehingga event listener AJAX yang nempel di situ tidak lepas
     tiap kali tambah/hapus petugas). --}}
@php
    // Hak sunting daftar Petugas = izin + kartunya tidak terkunci. Dihitung di
    // sini supaya tampilan sama dengan guard ensurePetugasOpen() di controller
    // (2026-09-26): dulu formnya tampil di Draft tapi kiriman ditolak 403.
    $petugasBoleh = auth()->user()->can('assignment_letter.manage') && $project->bolehIsiPetugas();
@endphp
<h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 dark:text-gray-400">
    Petugas <span class="font-normal normal-case text-gray-400">(tabel &quot;Adapun petugas kami&quot;)</span>
</h3>

{{-- Urutan bisa digeser (2026-09-24, feedback user): dulu memperbaiki urutan
     berarti menghapus lalu menambah ulang satu per satu. --}}
<div id="staffList" data-reorder-url="{{ route('projects.assignmentStaff.reorder', $project) }}">
@forelse ($project->assignmentStaff as $staff)
    <div class="staff-row flex items-center justify-between gap-2 py-1.5 text-sm border-b border-gray-50 last:border-b-0 dark:border-gray-700/60"
         data-id="{{ $staff->id }}" @if ($petugasBoleh) draggable="true" @endif>
        <div class="flex min-w-0 items-center gap-2">
            @if ($petugasBoleh)
                <span class="staff-grip cursor-grab text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="Geser untuk mengubah urutan" aria-hidden="true">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/>
                    </svg>
                </span>
            @endif
            <div class="min-w-0">
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $staff->user->name ?? '(pengguna terhapus)' }}</span>
            <span class="text-gray-400 dark:text-gray-400">
                {{-- Posisi manual menang atas Jabatan biodata (2026-09-26). --}}
                — {{ $staff->position ?: ($staff->user->jabatan ?? '(jabatan belum diisi)') }}
                @if ($staff->qualification)
                    &middot; {{ $staff->qualification }}
                @elseif ($project->butuhTimPelaksana())
                    {{-- Kolom Kualifikasi ada di tabel proposal, jadi kosongnya kelihatan. --}}
                    &middot; <span class="text-amber-600 dark:text-amber-400">kualifikasi belum diisi</span>
                @endif
                @if ($staff->user?->mappi_no) &middot; MAPPI {{ $staff->user->mappi_no }} @endif
            </span>
            </div>
        </div>
        @if ($petugasBoleh)
            {{-- Tanpa konfirmasi: notifikasinya membawa "Urungkan" (2026-09-25). --}}
            <form action="{{ route('projects.assignmentStaff.destroy', [$project, $staff]) }}" method="POST"
                  class="assignment-staff-remove-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
            </form>
        @endif
    </div>
@empty
    @if ($project->butuhTimPelaksana())
        {{-- Non-Penilaian: daftar ini dicetak sebagai tabel "Tim Pelaksana" di
             proposal, jadi kalau kosong proposalnya terbit tanpa tabel itu.
             Peringatannya dibuat mencolok (2026-09-26, permintaan user). --}}
        <div class="flex gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
            <svg class="mt-px h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-2.994-1.5-3.86 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <div>
                <p class="font-semibold">Petugas belum diisi — tabel &quot;Tim Pelaksana&quot; tidak akan tercetak di proposal.</p>
                <p class="mt-0.5">Tambahkan petugas di bawah ini dulu, lengkap dengan <span class="font-medium">Posisi</span> dan <span class="font-medium">Kualifikasi</span>, baru unduh proposalnya.</p>
            </div>
        </div>
    @else
        <p class="text-xs text-gray-400 dark:text-gray-400">Belum ada petugas ditambahkan.</p>
    @endif
@endforelse
</div>

@if ($petugasBoleh)
    @if ($project->assignmentStaff->count() > 1)
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">Geser baris untuk mengubah urutan cetak di Surat Tugas.</p>
    @endif

    <script>
    (function () {
        const daftar = document.getElementById('staffList');
        if (! daftar || daftar.dataset.siap) return;
        daftar.dataset.siap = '1';   // elemen baru sesudah render ulang AJAX belum bertanda

        let diseret = null;

        daftar.addEventListener('dragstart', function (e) {
            diseret = e.target.closest('.staff-row');
            if (! diseret) return;
            diseret.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
            // Tanpa setData, sebagian browser menolak drop dan kursornya jadi
            // tanda silang (2026-09-25, laporan user).
            try { e.dataTransfer.setData('text/plain', diseret.dataset.id || ''); } catch (err) {}
        });

        daftar.addEventListener('drop', function (e) {
            if (diseret) e.preventDefault();
        });

        daftar.addEventListener('dragend', function () {
            if (! diseret) return;
            diseret.style.opacity = '';
            diseret = null;
            simpanUrutan();
        });

        daftar.addEventListener('dragover', function (e) {
            if (! diseret) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';         // kursor "boleh drop"
            const target = e.target.closest('.staff-row');
            if (! target || target === diseret) return;

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
@endif

@if ($petugasBoleh)
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
        <p class="mt-3 text-xs text-gray-400 dark:text-gray-400">Semua pengguna aktif sudah ada di daftar petugas.</p>
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
            {{-- Posisi & Kualifikasi diketik manual (2026-09-26, permintaan user):
                 dipakai tabel Tim Pelaksana pada proposal konsultasi dan baris
                 Jabatan di Surat Tugas. Boleh dikosongkan. --}}
            <input type="text" name="position" maxlength="100" placeholder="Posisi (mis. Ketua Tim)"
                   class="min-w-0 flex-1 basis-40 h-[38px] rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600 dark:bg-gray-900">
            <input type="text" name="qualification" maxlength="120" placeholder="Kualifikasi (mis. Penilai Properti)"
                   class="min-w-0 flex-1 basis-40 h-[38px] rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600 dark:bg-gray-900">
            <button type="submit" class="inline-flex h-[38px] items-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700 whitespace-nowrap">
                + Tambah
            </button>
        </form>
    @endif
@endif
