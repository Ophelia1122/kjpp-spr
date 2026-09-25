{{-- Ubah di tempat (2026-09-25, feedback user): kartu yang sudah terisi
     mengunci isiannya sampai ikon pensil ditekan. Sekarang isian terkunci itu
     sendiri bisa diklik untuk membuka kartunya, lalu kursor langsung mendarat
     di kolom yang diklik. Tombol Edit dan Simpan TETAP ada — ini hanya jalan
     pintas, bukan penggantinya. --}}
<script>
(function () {
    // kartu => tombol pensil yang membukanya
    const KARTU = {
        'section-penilai':       'surveyEditBtn',
        'section-surat-tugas':   'suratEditBtn',
        'section-faktur':        'fakturEditBtn',
        'section-laporan-resmi': 'finalReportEditBtn',
    };

    Object.entries(KARTU).forEach(function ([idKartu, idTombol]) {
        const kartu = document.getElementById(idKartu);
        if (! kartu) return;

        kartu.addEventListener('click', function (e) {
            // Isian yang disabled tidak pernah mengirim event klik, jadi yang
            // tertangkap adalah pembungkusnya. Cari isian terkunci di dalamnya.
            let isian = e.target.closest('input, textarea, select');
            if (! isian) {
                const bungkus = e.target.closest('div');
                isian = bungkus && bungkus.querySelector(':scope > input:disabled, :scope > textarea:disabled, :scope > select:disabled');
            }
            if (! isian || ! isian.disabled) return;

            const tombol = document.getElementById(idTombol);
            if (! tombol || tombol.hidden) return;

            tombol.click();                       // buka kartu seperti biasa
            setTimeout(function () {
                if (isian.disabled) return;
                isian.focus({ preventScroll: true });
                if (isian.select) isian.select();
            }, 30);
        });

        // Petunjuk bahwa isian terkunci bisa diklik.
        kartu.querySelectorAll('input:disabled, textarea:disabled, select:disabled').forEach(function (el) {
            el.style.pointerEvents = 'none';      // klik jatuh ke pembungkusnya
            el.parentElement?.classList.add('cursor-pointer');
            el.title = 'Klik untuk mengubah';
        });
    });
})();
</script>
