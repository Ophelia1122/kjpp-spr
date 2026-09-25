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
        isi.forEach(el => { el.hidden = true; });

        kepala.classList.add('cursor-pointer');
        kepala.setAttribute('role', 'button');
        kepala.setAttribute('tabindex', '0');
        kepala.title = 'Klik untuk membuka kartu ini';

        const tanda = document.createElement('span');
        tanda.className = 'ml-auto shrink-0 text-[11px] font-medium text-gray-400 dark:text-gray-500';
        tanda.textContent = 'Buka';
        kepala.classList.add('flex', 'items-center', 'gap-2');
        kepala.appendChild(tanda);

        function buka() {
            isi.forEach(el => { el.hidden = false; });
            tanda.remove();
            kepala.classList.remove('cursor-pointer');
            kepala.removeAttribute('role');
            kepala.removeAttribute('tabindex');
            kepala.removeAttribute('title');
        }

        kepala.addEventListener('click', buka);
        kepala.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); buka(); } });
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
