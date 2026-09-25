{{-- Kartu yang belum waktunya dilipat otomatis (2026-09-25, feedback user):
     halaman proyek punya 7-8 kartu, padahal tiap tahap hanya butuh satu-dua.
     Yang dilipat tetap ada, tinggal diklik judulnya. Butuh $project. --}}
@php
    use App\Models\Project;

    // Kartu yang RELEVAN di tiap status; sisanya dilipat.
    $relevan = match ($project->status) {
        Project::STATUS_DRAFT, Project::STATUS_WAITING_APPROVAL
            => ['section-info', 'section-tagihan', 'section-aksi'],
        Project::STATUS_DP_INVOICING
            => ['section-info', 'section-tagihan', 'section-faktur', 'section-aksi'],
        Project::STATUS_IN_PROGRESS
            => ['section-info', 'section-penilai', 'section-surat-tugas', 'section-aksi'],
        Project::STATUS_FINALISASI
            => ['section-info', 'section-penilai', 'section-laporan-resmi', 'section-aksi'],
        Project::STATUS_TANDA_TANGAN
            => ['section-info', 'section-laporan-resmi', 'section-aksi'],
        Project::STATUS_PENGIRIMAN
            => ['section-info', 'section-tanda-terima', 'section-tagihan', 'section-aksi'],
        default
            => ['section-info', 'section-tagihan', 'section-tanda-terima', 'section-aksi'],
    };
@endphp

@unless ($errors->any())
<script>
(function () {
    const RELEVAN = @json($relevan);
    const SEMUA = ['section-info', 'section-penilai', 'section-surat-tugas', 'section-tagihan',
                   'section-faktur', 'section-laporan-resmi', 'section-tanda-terima', 'section-aksi'];

    function lipat(kartu) {
        const anak = [...kartu.children];
        if (anak.length < 2) return;

        const kepala = anak[0];
        const isi    = anak.slice(1);
        const judul  = kepala.querySelector('h2, h3') || kepala;
        let terbuka  = false;

        // Tanda panah ditaruh DI DEPAN nama kartu, bukan di kanan: tombol
        // Edit/Simpan di kanan tidak boleh tergeser (2026-09-25, feedback user).
        const panah = document.createElement('button');
        panah.type = 'button';
        panah.setAttribute('aria-expanded', 'false');
        panah.className = 'mr-1.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300';
        panah.innerHTML = '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>';
        panah.title = 'Buka kartu ini';
        judul.prepend(panah);

        function setel(buka) {
            terbuka = buka;
            isi.forEach(el => { el.hidden = ! buka; });
            panah.style.transform = buka ? 'rotate(90deg)' : '';
            panah.setAttribute('aria-expanded', buka ? 'true' : 'false');
            panah.title = buka ? 'Tutup kartu ini' : 'Buka kartu ini';
        }

        setel(false);

        function toggle(e) {
            e.preventDefault();
            e.stopPropagation();
            setel(! terbuka);
        }

        panah.addEventListener('click', toggle);
        judul.addEventListener('click', function (e) {
            // Klik nama kartu ikut membuka, tetapi klik tombol di dalamnya tidak.
            if (e.target.closest('a, button') && e.target.closest('button') !== panah) return;
            toggle(e);
        });
        judul.classList.add('cursor-pointer');
    }

    SEMUA.filter(id => ! RELEVAN.includes(id)).forEach(function (id) {
        const kartu = document.getElementById(id);
        if (! kartu) return;
        // Halaman yang dibuka langsung ke kartu tertentu lewat tautan
        // #section-... tidak ikut dilipat. Saat ada galat validasi, skrip ini
        // tidak dirender sama sekali oleh Blade.
        if (window.location.hash === '#' + id) return;
        lipat(kartu);
    });
})();
</script>
@endunless
