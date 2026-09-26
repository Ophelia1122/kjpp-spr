{{-- Palet pencarian cepat Ctrl+K (2026-09-25, feedback user): lompat ke proyek
     atau menu tanpa pindah halaman dulu. Hanya untuk yang boleh melihat
     proposal; menu yang tampil mengikuti izin masing-masing. --}}
@can('proposals.view')
@php
    $menuPalet = collect([
        ['judul' => 'Beranda',             'url' => route('home'),                 'izin' => null],
        ['judul' => 'List Project',        'url' => route('dashboard'),            'izin' => 'dashboard.view'],
        ['judul' => 'Proposal Baru — Penilaian Properti', 'url' => route('proposals.create'), 'izin' => 'proposals.manage'],
        ['judul' => 'Proposal Baru — Kajian Kewajaran RAB', 'url' => route('proposals.create', ['layanan' => \App\Models\Project::SERVICE_KONSULTASI, 'jenis' => \App\Models\Project::CONSULTING_RAB]), 'izin' => 'proposals.manage'],
        ['judul' => 'Proposal Baru — Studi Kelayakan', 'url' => route('proposals.create', ['layanan' => \App\Models\Project::SERVICE_KONSULTASI, 'jenis' => \App\Models\Project::CONSULTING_FS]), 'izin' => 'proposals.manage'],
        ['judul' => 'Proposal Baru — Pengawasan Proyek', 'url' => route('proposals.create', ['layanan' => \App\Models\Project::SERVICE_KONSULTASI, 'jenis' => \App\Models\Project::CONSULTING_PENGAWASAN]), 'izin' => 'proposals.manage'],
        ['judul' => 'Timeline Project',    'url' => route('timeline'),             'izin' => 'dashboard.view'],
        ['judul' => 'SPJ Surveyor',        'url' => route('spj.index'),            'izin' => 'survey.view'],
        ['judul' => 'Ringkasan Project',   'url' => route('dashboard.overview'),   'izin' => 'dashboard.overview'],
        ['judul' => 'Dashboard Pembayaran','url' => route('dashboard.pembayaran'), 'izin' => 'invoices.view'],
        ['judul' => 'Database Klien',      'url' => route('clients.index'),        'izin' => 'clients.view'],
        ['judul' => 'Kelola Pengguna',     'url' => route('users.index'),          'izin' => 'users.manage'],
        ['judul' => 'Log Aktivitas',       'url' => route('audit.index'),          'izin' => 'audit.view'],
    ])->filter(fn ($m) => ! $m['izin'] || auth()->user()->can($m['izin']))->values();
@endphp

<div id="quickPalette" class="fixed inset-0 z-[90] hidden items-start justify-center bg-gray-900/50 p-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="Pencarian cepat">
    <div class="w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-800">
        <div class="flex items-center gap-2 border-b border-gray-100 px-4 dark:border-gray-700">
            <svg aria-hidden="true" class="h-[18px] w-[18px] shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" id="qpInput" autocomplete="off" placeholder="Cari proyek atau menu…"
                   class="w-full border-0 bg-transparent py-3 text-sm focus:outline-none focus:ring-0 dark:text-gray-100">
            <kbd class="shrink-0 rounded border border-gray-200 px-1.5 text-[10px] text-gray-400 dark:border-gray-600 dark:text-gray-500">Esc</kbd>
        </div>
        <div id="qpHasil" class="max-h-[50vh] overflow-y-auto py-1"></div>
    </div>
</div>

<script>
(function () {
    const MENU  = @json($menuPalet);
    const CARI  = @json(route('quickSearch'));
    const modal = document.getElementById('quickPalette');
    const input = document.getElementById('qpInput');
    const hasil = document.getElementById('qpHasil');
    document.body.appendChild(modal);

    let baris = [];
    let pilih = 0;
    let timer = null;

    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function gambar() {
        hasil.innerHTML = baris.length
            ? baris.map((b, i) => `
                <button type="button" data-i="${i}"
                        class="qp-item flex w-full items-center gap-3 px-4 py-2 text-left text-sm ${i === pilih ? 'bg-blue-50 dark:bg-blue-900/30' : ''}">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium text-gray-800 dark:text-gray-100">${esc(b.judul)}</span>
                        ${b.sub ? `<span class="block truncate text-xs text-gray-400 dark:text-gray-500">${esc(b.sub)}</span>` : ''}
                    </span>
                    ${b.status ? `<span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-300">${esc(b.status)}</span>` : ''}
                </button>`).join('')
            : '<p class="px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada yang cocok.</p>';

        hasil.querySelectorAll('.qp-item').forEach(el => {
            el.addEventListener('click', () => buka(baris[+el.dataset.i]));
        });
    }

    function buka(b) { if (b) window.location.href = b.url; }

    function menuCocok(kata) {
        const k = kata.toLowerCase();
        return MENU.filter(m => ! k || m.judul.toLowerCase().includes(k))
            .map(m => ({ judul: m.judul, sub: 'Menu', url: m.url, status: '' }));
    }

    async function cari(kata) {
        baris = menuCocok(kata);
        pilih = 0;
        gambar();

        if (kata.length < 2) return;

        try {
            const r = await fetch(CARI + '?q=' + encodeURIComponent(kata), { headers: { Accept: 'application/json' } });
            const proyek = await r.json();
            if (input.value.trim() !== kata) return;          // sudah diketik lagi
            baris = [...proyek, ...menuCocok(kata)];
            gambar();
        } catch (e) { /* daftar menu saja sudah cukup */ }
    }

    function tampil() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        input.value = '';
        cari('');
        input.focus();
    }

    function tutup() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const kata = input.value.trim();
        timer = setTimeout(() => cari(kata), 200);
    });

    modal.addEventListener('click', e => { if (e.target === modal) tutup(); });

    document.addEventListener('keydown', function (e) {
        const buka2 = (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k';
        if (buka2) { e.preventDefault(); modal.classList.contains('hidden') ? tampil() : tutup(); return; }

        if (modal.classList.contains('hidden')) return;

        if (e.key === 'Escape') { e.preventDefault(); tutup(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); pilih = Math.min(pilih + 1, baris.length - 1); gambar(); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); pilih = Math.max(pilih - 1, 0); gambar(); }
        else if (e.key === 'Enter')     { e.preventDefault(); buka(baris[pilih]); }
    });
})();
</script>
@endcan
