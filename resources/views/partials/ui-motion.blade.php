{{--
    =========================================================================
    GERAK & UMPAN BALIK ANTARMUKA (2026-09-25, permintaan user)

    Satu berkas untuk seluruh animasi kecil yang dipakai di semua halaman:
      1. Tombol unduh (PDF/Word/Excel) jadi berputar sampai berkasnya benar-
         benar turun — mencegah klik berulang saat LibreOffice masih bekerja.
      2. Notifikasi hijau/merah meluncur masuk lalu menutup sendiri.
      3. Elemen tujuan tautan (#fragment) berdenyut sekali supaya mata
         langsung menemukan baris yang baru berubah.
      4. Angka kartu Beranda naik dari 0.
      5. Konfeti saat proyek ditutup (session 'konfetti').

    Semua mematuhi prefers-reduced-motion; bila pengguna meminta kurangi
    animasi, geraknya dilewati tetapi fungsinya tetap jalan.
    =========================================================================
--}}
@php
    // Rute yang balasannya berupa berkas unduhan. Dipakai untuk mengenali
    // tautan unduhan tanpa harus menandai satu per satu di tiap halaman.
    $polaUnduhan = collect([
        'proposals.exportPdf', 'proposals.exportWord', 'proposals.exportRepresentatif',
        'projects.exportSuratTugas', 'projects.exportSuratTugasWord',
        'invoices.exportInvoice', 'invoices.exportInvoiceWord',
        'invoices.exportKwitansi', 'invoices.exportKwitansiWord',
        'receipts.pdf', 'receipts.word',
        'audit.export', 'spj.export', 'dashboard.exportExcel',
    ])->map(function (string $nama) {
        $rute = \Illuminate\Support\Facades\Route::getRoutes()->getByName($nama);

        return $rute ? '^/' . preg_replace('/\{[^}]+\}/', '[^/]+', $rute->uri()) . '$' : null;
    })->filter()->values();
@endphp

<style>
    /* ---------- 1. Tombol sedang menyiapkan berkas ---------- */
    @keyframes ui-spin { to { transform: rotate(360deg); } }
    .ui-spinner {
        display: inline-block; width: 1em; height: 1em; border-radius: 9999px;
        border: 2px solid currentColor; border-right-color: transparent;
        animation: ui-spin .6s linear infinite; vertical-align: -.125em;
    }

    /* ---------- 2. Notifikasi ---------- */
    @keyframes ui-toast-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
    @keyframes ui-toast-out { to { opacity: 0; transform: translateY(-8px); } }
    [data-toast] { position: relative; overflow: hidden; animation: ui-toast-in .22s cubic-bezier(.16,.84,.44,1) both; }
    [data-toast].ui-toast-pergi { animation: ui-toast-out .2s ease-in both; }
    .ui-toast-sisa {
        position: absolute; left: 0; bottom: 0; height: 2px; width: 100%;
        background: currentColor; opacity: .35; transform-origin: left center;
        animation: ui-toast-habis linear both;
    }
    @keyframes ui-toast-habis { from { transform: scaleX(1); } to { transform: scaleX(0); } }
    [data-toast]:hover .ui-toast-sisa, [data-toast]:focus-within .ui-toast-sisa { animation-play-state: paused; }

    /* ---------- 3. Sorot elemen tujuan ---------- */
    @keyframes ui-sorot {
        0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        18%  { box-shadow: 0 0 0 4px rgba(245, 158, 11, .45); }
        100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }
    .ui-sorot { animation: ui-sorot 1.8s ease-out 1; border-radius: .5rem; }

    /* ---------- 5. Konfeti ---------- */
    @keyframes ui-konfeti-jatuh {
        from { transform: translate3d(0, -10vh, 0) rotate(0deg); opacity: 1; }
        to   { transform: translate3d(var(--geser), 104vh, 0) rotate(var(--putar)); opacity: .9; }
    }
    #uiKonfeti { position: fixed; inset: 0; pointer-events: none; z-index: 70; overflow: hidden; }
    #uiKonfeti i {
        position: absolute; top: 0; display: block; width: 8px; height: 14px; border-radius: 1px;
        animation: ui-konfeti-jatuh var(--lama) cubic-bezier(.25,.6,.5,1) var(--tunda) both;
    }

    @media (prefers-reduced-motion: reduce) {
        .ui-sorot, [data-toast], [data-toast].ui-toast-pergi, .ui-toast-sisa { animation: none; }
        #uiKonfeti { display: none; }
    }
</style>

<script>
(function () {
    'use strict';

    const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* =====================================================================
       1. UNDUHAN: tombol berputar sementara berkas disiapkan.

       Unduhan TETAP ditangani browser seperti biasa (tidak diambil alih
       JavaScript) supaya tidak ada berkas yang gagal turun. Yang kita
       tambahkan hanya penanda sibuk: tombolnya berputar dan tidak bisa
       diklik lagi, sampai jendela kehilangan fokus (kotak "Simpan berkas"
       muncul) atau paling lama 12 detik.
       ===================================================================== */
    const polaUnduhan = @json($polaUnduhan).map((p) => new RegExp(p));
    const basisUrl    = @json(request()->getBaseUrl());

    function mulaiSibuk(tautan) {
        tautan.dataset.mengunduh = '1';
        tautan.setAttribute('aria-busy', 'true');
        tautan.dataset.isiAsli = tautan.innerHTML;
        tautan.classList.add('opacity-60', 'cursor-wait');

        const label = (tautan.textContent || '').trim();
        tautan.innerHTML = label
            ? '<span class="inline-flex items-center gap-1.5"><span class="ui-spinner"></span>Menyiapkan…</span>'
            : '<span class="ui-spinner"></span>';
    }

    function selesaiSibuk(tautan) {
        if (! tautan.dataset.mengunduh) return;
        delete tautan.dataset.mengunduh;
        tautan.removeAttribute('aria-busy');
        tautan.classList.remove('opacity-60', 'cursor-wait');
        if (tautan.dataset.isiAsli !== undefined) {
            tautan.innerHTML = tautan.dataset.isiAsli;
            delete tautan.dataset.isiAsli;
        }
    }

    document.addEventListener('click', function (e) {
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.defaultPrevented) return;

        const tautan = e.target.closest('a[href]');
        if (! tautan) return;

        let url;
        try { url = new URL(tautan.href, location.href); } catch (err) { return; }
        if (url.origin !== location.origin) return;

        let jalur = url.pathname;
        if (basisUrl && jalur.indexOf(basisUrl) === 0) jalur = jalur.slice(basisUrl.length);
        if (! polaUnduhan.some((pola) => pola.test(jalur))) return;

        // Klik kedua diabaikan selama berkas pertama masih disiapkan.
        if (tautan.dataset.mengunduh) { e.preventDefault(); return; }

        mulaiSibuk(tautan);

        const bereskan = function () {
            clearTimeout(waktu);
            window.removeEventListener('blur', bereskan);
            window.removeEventListener('pageshow', bereskan);
            setTimeout(() => selesaiSibuk(tautan), 400);
        };
        const waktu = setTimeout(bereskan, 12000);
        window.addEventListener('blur', bereskan, { once: true });
        window.addEventListener('pageshow', bereskan, { once: true });
    });

    /* =====================================================================
       2. NOTIFIKASI: meluncur masuk, menutup sendiri, berhenti saat disentuh.
       Notifikasi yang memuat tombol "Urungkan" diberi waktu lebih lama.
       ===================================================================== */
    document.querySelectorAll('[data-toast]').forEach(function (kotak) {
        const lama = parseInt(kotak.dataset.toast, 10);
        if (! lama || kurangiGerak) return;

        const sisa = document.createElement('div');
        sisa.className = 'ui-toast-sisa';
        sisa.style.animationDuration = lama + 'ms';
        kotak.appendChild(sisa);

        sisa.addEventListener('animationend', function () {
            kotak.classList.add('ui-toast-pergi');
            kotak.addEventListener('animationend', () => kotak.remove(), { once: true });
        });
    });

    /* =====================================================================
       3. SOROT elemen tujuan tautan (#fragment) — dipakai setelah simpan,
       ketika halaman kembali ke posisi semula.
       ===================================================================== */
    function sorotTujuan() {
        if (! location.hash || location.hash.length < 2) return;

        let tujuan = null;
        try { tujuan = document.querySelector(location.hash); } catch (err) { return; }
        if (! tujuan) return;

        tujuan.classList.remove('ui-sorot');
        void tujuan.offsetWidth;            // paksa mulai ulang animasinya
        tujuan.classList.add('ui-sorot');
    }
    sorotTujuan();
    window.addEventListener('hashchange', sorotTujuan);

    /* =====================================================================
       4. ANGKA KARTU naik dari 0 saat kartunya masuk layar. Hanya bagian
       angkanya yang dihitung; awalan "Rp" dan satuan tetap apa adanya.
       ===================================================================== */
    function hitungNaik(el) {
        const teks = el.textContent.trim();
        const cocok = /^(\D*?)([\d.,]+)(.*)$/s.exec(teks);
        if (! cocok) return;

        const angkaTeks = cocok[2];
        const pemisah   = angkaTeks.includes('.') ? '.' : (angkaTeks.includes(',') ? ',' : '');
        const tujuan    = parseInt(angkaTeks.replace(/[^\d]/g, ''), 10);
        if (! isFinite(tujuan) || tujuan <= 0) return;

        const rapikan = (n) => pemisah ? n.toLocaleString('id-ID').replace(/\./g, pemisah) : String(n);
        const mulai = performance.now();
        const lama  = 650;

        function langkah(saat) {
            const maju = Math.min(1, (saat - mulai) / lama);
            const halus = 1 - Math.pow(1 - maju, 3);
            el.textContent = cocok[1] + rapikan(Math.round(tujuan * halus)) + cocok[3];
            if (maju < 1) requestAnimationFrame(langkah);
        }
        requestAnimationFrame(langkah);
    }

    const kartuAngka = document.querySelectorAll('[data-angka]');
    if (kartuAngka.length && ! kurangiGerak && 'IntersectionObserver' in window) {
        const pengamat = new IntersectionObserver(function (entri) {
            entri.forEach(function (satu) {
                if (! satu.isIntersecting) return;
                pengamat.unobserve(satu.target);
                hitungNaik(satu.target);
            });
        }, { threshold: .4 });
        kartuAngka.forEach((el) => pengamat.observe(el));
    }

    /* =====================================================================
       5. KONFETI saat proyek ditutup. Murni DOM + CSS, tanpa pustaka.
       ===================================================================== */
    window.uiKonfeti = function (jumlah) {
        if (kurangiGerak) return;

        const warna = ['#2563eb', '#059669', '#f59e0b', '#e11d48', '#7c3aed', '#0891b2'];
        const wadah = document.createElement('div');
        wadah.id = 'uiKonfeti';
        wadah.setAttribute('aria-hidden', 'true');

        for (let i = 0; i < (jumlah || 90); i++) {
            const keping = document.createElement('i');
            keping.style.left  = Math.random() * 100 + 'vw';
            keping.style.background = warna[i % warna.length];
            keping.style.setProperty('--geser', (Math.random() * 30 - 15) + 'vw');
            keping.style.setProperty('--putar', (Math.random() * 1080 - 540) + 'deg');
            keping.style.setProperty('--lama',  (2.2 + Math.random() * 1.6) + 's');
            keping.style.setProperty('--tunda', (Math.random() * .5) + 's');
            keping.style.opacity = .85;
            if (i % 3 === 0) { keping.style.width = '6px'; keping.style.height = '6px'; keping.style.borderRadius = '9999px'; }
            wadah.appendChild(keping);
        }

        document.body.appendChild(wadah);
        setTimeout(() => wadah.remove(), 4600);
    };

    @if (session('konfetti'))
        window.uiKonfeti();
    @endif
})();
</script>
