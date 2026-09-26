{{--
    Pemilih Jenis Layanan sebelum form Buat Proposal (2026-09-25, permintaan user).

    Isi form penilaian dan konsultasi berbeda cukup jauh, jadi jenisnya dipilih
    LEBIH DULU — bukan di dalam form. Kalau pilihannya ada di dalam form lalu
    diganti di tengah jalan, isian yang sudah diketik jadi tersembunyi.

    Dipakai dengan tombol apa pun:  onclick="bukaPilihLayanan()"
--}}
@php
    // Dua pilihan saja (2026-09-26, permintaan user): isian formnya sama untuk
    // ketiga jenis Non-Penilaian, jadi jenisnya cukup dipilih di dalam form —
    // sama seperti Tujuan Penilaian pada proposal penilaian.
    $pilihan = collect([
        [
            'grup'  => 'PENILAIAN',
            'judul' => \App\Models\Project::SERVICE_LABELS[\App\Models\Project::SERVICE_PENILAIAN],
            'sub'   => 'Tujuan penilaian dipilih di dalam form.',
            'url'   => route('proposals.create'),
            'ikon'  => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        ],
        [
            'grup'  => 'NON-PENILAIAN',
            'judul' => \App\Models\Project::SERVICE_LABELS[\App\Models\Project::SERVICE_KONSULTASI],
            'sub'   => 'Studi Kelayakan / Kajian Kewajaran RAB / Pengawasan Proyek — jenis pekerjaan dipilih di dalam form.',
            'url'   => route('proposals.create', ['layanan' => \App\Models\Project::SERVICE_KONSULTASI]),
            'ikon'  => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        ],
    ]);
@endphp

<div id="pilihLayananModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/60 p-4"
     role="dialog" aria-modal="true" aria-labelledby="pilihLayananJudul"
     onclick="if (event.target === this) tutupPilihLayanan()">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="pilihLayananJudul" class="text-base font-semibold text-gray-900 dark:text-gray-100">Proposal Baru</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Pilih jenis pekerjaannya dulu — isian formnya menyesuaikan.</p>
            </div>
            <button type="button" onclick="tutupPilihLayanan()" aria-label="Tutup"
                    class="text-2xl leading-none text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>

        {{-- Dua pilihan saja, tanpa judul kelompok — judulnya sudah mewakili. --}}
        <div class="mt-4 space-y-2">
            @foreach ($pilihan as $item)
                <a href="{{ $item['url'] }}"
                   class="flex items-start gap-3 rounded-lg border border-gray-200 px-3 py-3 text-left transition hover:border-blue-400 hover:bg-blue-50 dark:border-gray-700 dark:hover:border-blue-700 dark:hover:bg-blue-900/20">
                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-md bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-300">
                        <svg aria-hidden="true" class="h-[20px] w-[20px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['judul'] }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item['sub'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('pilihLayananModal');
        document.body.appendChild(modal);

        window.bukaPilihLayanan = function () {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.querySelector('a')?.focus();
        };

        window.tutupPilihLayanan = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };
    })();
</script>
