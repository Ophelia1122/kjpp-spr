{{-- Penanda galat per bagian form (2026-09-25, feedback user): form proposal
     punya 5 kartu dengan puluhan isian, dan saat validasi gagal staf harus
     mencari sendiri isian merahnya. Pill bagian yang bermasalah diberi titik
     merah, lalu halaman melompat ke isian pertama yang salah. --}}
@if ($errors->any())
    <script>
    (function () {
        function tandai() {
            const bagian = [...document.querySelectorAll('[id^="section-"]')];
            let pertama = null;

            bagian.forEach(function (kartu) {
                const galat = kartu.querySelector('.text-red-600, [aria-invalid="true"], .border-red-400');
                const pill  = document.querySelector('[data-target="' + kartu.id + '"]');

                if (! galat) return;
                if (! pertama) pertama = galat;

                if (pill && ! pill.querySelector('.form-error-dot')) {
                    const titik = document.createElement('span');
                    titik.className = 'form-error-dot ml-1 inline-block h-1.5 w-1.5 rounded-full bg-red-500 align-middle';
                    titik.title = 'Ada isian yang perlu diperbaiki di bagian ini';
                    pill.appendChild(titik);
                    pill.classList.add('text-red-600', 'dark:text-red-400');
                }
            });

            if (pertama) {
                const isian = pertama.closest('div')?.querySelector('input, select, textarea');
                (isian || pertama).scrollIntoView({ block: 'center', behavior: 'smooth' });
                if (isian && ! isian.disabled) setTimeout(() => isian.focus({ preventScroll: true }), 350);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', tandai);
        } else {
            tandai();
        }
    })();
    </script>
@endif
