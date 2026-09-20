{{-- Foto profil yang bisa langsung diedit (2026-09-15, feedback user): SATU
     lingkaran foto dengan ikon Edit (ganti) & Trash (hapus), caption kecil
     berlebar tetap di bawahnya. Foto yang dipilih dibuka di editor crop
     (geser + zoom dalam bingkai bulat), lalu dikirim sebagai JPG 512x512.
     Dipakai users/create, users/edit & profile/show.
     Form induk wajib enctype="multipart/form-data".
     Param: $avatarUser (User|null), $avatarForm (id form bila partial ini
     berada di luar <form>, opsional), $avatarSize ('lg' | 'md', default md). --}}
@php
    $avatarUser = $avatarUser ?? null;
    $avatarForm = $avatarForm ?? null;
    $big        = ($avatarSize ?? 'md') === 'lg';
    $hasPhoto   = (bool) $avatarUser?->avatar_url;
@endphp
<div class="flex w-28 shrink-0 flex-col items-center gap-1.5" data-avatar-uploader>
    <div class="relative {{ $big ? 'h-24 w-24' : 'h-20 w-20' }}">
        <span class="grid h-full w-full place-items-center overflow-hidden rounded-full bg-blue-600 font-semibold text-white uppercase ring-4 ring-white shadow-sm {{ $big ? 'text-3xl' : 'text-2xl' }} dark:ring-gray-800">
            <span data-avatar-initials @if ($hasPhoto) hidden @endif>{{ $avatarUser?->initials ?: '?' }}</span>
            <img data-avatar-preview @if ($hasPhoto) src="{{ $avatarUser->avatar_url }}" @else hidden @endif
                 alt="Foto profil" class="h-full w-full object-cover">
        </span>

        <button type="button" data-avatar-edit title="{{ $hasPhoto ? 'Ganti foto profil' : 'Unggah foto profil' }}" aria-label="{{ $hasPhoto ? 'Ganti foto profil' : 'Unggah foto profil' }}"
                class="absolute -bottom-0.5 -right-0.5 grid h-8 w-8 place-items-center rounded-full border border-gray-200 bg-white text-gray-600 shadow hover:bg-blue-50 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-blue-900/40">
            @include('partials.icon-pencil')
        </button>
        <button type="button" data-avatar-trash title="Hapus foto profil" aria-label="Hapus foto profil" @unless ($hasPhoto) hidden @endunless
                class="absolute -bottom-0.5 -left-0.5 grid h-8 w-8 place-items-center rounded-full border border-gray-200 bg-white text-gray-500 shadow hover:bg-red-50 hover:text-red-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-red-900/40">
            @include('partials.icon-trash')
        </button>

        <input type="file" name="avatar" accept="image/jpeg,image/png" hidden data-avatar-input @if ($avatarForm) form="{{ $avatarForm }}" @endif>
        <input type="hidden" name="remove_avatar" value="0" data-avatar-remove @if ($avatarForm) form="{{ $avatarForm }}" @endif>
    </div>
    {{-- Lebar tetap (w-28) & teks status pendek — caption tidak lagi melebarkan kolom foto. --}}
    <p data-avatar-caption class="w-full text-center text-[11px] leading-tight text-gray-400 dark:text-gray-500">JPG/PNG · maks. 1 MB</p>
    @error('avatar') <p class="w-full text-center text-[11px] leading-tight text-red-600">{{ $message }}</p> @enderror

    {{-- ===================== EDITOR CROP ===================== --}}
    <div data-avatar-cropper hidden class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 px-4" role="dialog" aria-modal="true" aria-label="Atur foto profil">
        <div class="w-full max-w-sm rounded-xl bg-white p-5 text-left shadow-xl dark:bg-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Atur Foto Profil</h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Geser foto & atur zoom supaya wajah pas di dalam lingkaran.</p>

            <div data-crop-stage class="relative mx-auto mt-4 aspect-square w-full max-w-[280px] cursor-grab touch-none select-none overflow-hidden rounded-lg bg-gray-900 active:cursor-grabbing">
                <img data-crop-image alt="" draggable="false" class="absolute left-0 top-0 max-w-none origin-top-left">
                <div class="pointer-events-none absolute inset-0 rounded-full ring-2 ring-white/80" style="box-shadow: 0 0 0 9999px rgba(0,0,0,.55)"></div>
            </div>

            <div class="mt-4 flex items-center gap-3 text-gray-400">
                <svg aria-hidden="true" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM13.5 10.5h-6"/></svg>
                <input type="range" data-crop-zoom min="1" max="4" step="0.01" value="1" aria-label="Zoom" class="w-full accent-blue-600">
                <svg aria-hidden="true" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6"/></svg>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-crop-cancel class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                <button type="button" data-crop-apply class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Gunakan Foto</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        var root    = document.currentScript.previousElementSibling;
        var input   = root.querySelector('[data-avatar-input]');
        var remove  = root.querySelector('[data-avatar-remove]');
        var img     = root.querySelector('[data-avatar-preview]');
        var initial = root.querySelector('[data-avatar-initials]');
        var trash   = root.querySelector('[data-avatar-trash]');
        var caption = root.querySelector('[data-avatar-caption]');
        var original = img.getAttribute('src');
        var baseCaption = caption.textContent;

        var OUTPUT = 512;                 // sisi foto hasil crop (px)
        var MAX_SOURCE = 10 * 1024 * 1024; // foto asli boleh besar; hasil crop dikompres jauh di bawah 1 MB
        var committed = null;             // File hasil crop yang sedang terpasang di input

        function show(src, note, isError) {
            img.hidden = !src;
            if (src) img.src = src;
            initial.hidden = !!src;
            trash.hidden = !src;
            caption.textContent = note || baseCaption;
            caption.classList.toggle('text-red-600', !!isError);
            caption.classList.toggle('text-blue-600', !!note && !isError);
        }
        function setInputFile(file) {
            try {
                var dt = new DataTransfer();
                if (file) dt.items.add(file);
                input.files = dt.files;
                return true;
            } catch (e) { return false; }
        }

        // ---------- Editor crop ----------
        var modal = root.querySelector('[data-avatar-cropper]');
        document.body.appendChild(modal); // hindari ikut terpotong/ter-transform elemen induk
        var stage = modal.querySelector('[data-crop-stage]');
        var cImg  = modal.querySelector('[data-crop-image]');
        var zoomEl = modal.querySelector('[data-crop-zoom]');
        var st = { S: 0, base: 1, zoom: 1, x: 0, y: 0, w: 0, h: 0 };
        var sourceUrl = null;

        function scale() { return st.base * st.zoom; }
        function clamp() {
            var s = scale();
            st.x = Math.min(0, Math.max(st.S - st.w * s, st.x));
            st.y = Math.min(0, Math.max(st.S - st.h * s, st.y));
        }
        function render() {
            clamp();
            var s = scale();
            cImg.style.width  = (st.w * s) + 'px';
            cImg.style.height = (st.h * s) + 'px';
            cImg.style.transform = 'translate(' + st.x + 'px,' + st.y + 'px)';
        }
        function setZoom(z, cx, cy) {
            z = Math.min(4, Math.max(1, z));
            cx = cx == null ? st.S / 2 : cx;
            cy = cy == null ? st.S / 2 : cy;
            var old = scale();
            var px = (cx - st.x) / old, py = (cy - st.y) / old; // titik foto di bawah kursor/tengah tetap diam
            st.zoom = z;
            st.x = cx - px * scale();
            st.y = cy - py * scale();
            zoomEl.value = z;
            render();
        }
        function openCropper(file) {
            if (sourceUrl) URL.revokeObjectURL(sourceUrl);
            sourceUrl = URL.createObjectURL(file);
            cImg.onload = function () {
                modal.hidden = false;
                st.S = stage.clientWidth;
                st.w = cImg.naturalWidth;
                st.h = cImg.naturalHeight;
                st.base = Math.max(st.S / st.w, st.S / st.h); // zoom 1 = foto menutupi bingkai
                st.zoom = 1;
                zoomEl.value = 1;
                st.x = (st.S - st.w * st.base) / 2;
                st.y = (st.S - st.h * st.base) / 2;
                render();
                modal.querySelector('[data-crop-apply]').focus();
            };
            cImg.src = sourceUrl;
        }
        function closeCropper() {
            modal.hidden = true;
            setInputFile(committed); // Batal: kembalikan pilihan sebelumnya (atau kosong)
        }

        // Ukuran bingkai berubah (HP diputar / jendela di-resize) -> skala ulang posisi foto.
        window.addEventListener('resize', function () {
            if (modal.hidden || !st.S) return;
            var ratio = stage.clientWidth / st.S;
            if (!ratio || ratio === 1) return;
            st.S *= ratio; st.base *= ratio; st.x *= ratio; st.y *= ratio;
            render();
        });

        zoomEl.addEventListener('input', function () { setZoom(parseFloat(zoomEl.value)); });
        stage.addEventListener('wheel', function (e) {
            e.preventDefault();
            var r = stage.getBoundingClientRect();
            setZoom(st.zoom * (e.deltaY < 0 ? 1.08 : 1 / 1.08), e.clientX - r.left, e.clientY - r.top);
        }, { passive: false });

        // Geser (mouse/jari) & cubit dua jari untuk zoom.
        var pointers = new Map(), pinch = null;
        stage.addEventListener('pointerdown', function (e) {
            stage.setPointerCapture(e.pointerId);
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pointers.size === 2) {
                var p = Array.from(pointers.values());
                pinch = { d: Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y), zoom: st.zoom };
            }
        });
        stage.addEventListener('pointermove', function (e) {
            var prev = pointers.get(e.pointerId);
            if (!prev) return;
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pointers.size === 2 && pinch) {
                var p = Array.from(pointers.values()), r = stage.getBoundingClientRect();
                setZoom(pinch.zoom * Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y) / pinch.d,
                        (p[0].x + p[1].x) / 2 - r.left, (p[0].y + p[1].y) / 2 - r.top);
            } else if (pointers.size === 1) {
                st.x += e.clientX - prev.x;
                st.y += e.clientY - prev.y;
                render();
            }
        });
        ['pointerup', 'pointercancel'].forEach(function (t) {
            stage.addEventListener(t, function (e) { pointers.delete(e.pointerId); if (pointers.size < 2) pinch = null; });
        });

        modal.querySelector('[data-crop-cancel]').addEventListener('click', closeCropper);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeCropper(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeCropper(); });

        modal.querySelector('[data-crop-apply]').addEventListener('click', function () {
            var s = scale();
            var canvas = document.createElement('canvas');
            canvas.width = canvas.height = OUTPUT;
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; // PNG transparan -> latar putih
            ctx.fillRect(0, 0, OUTPUT, OUTPUT);
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(cImg, -st.x / s, -st.y / s, st.S / s, st.S / s, 0, 0, OUTPUT, OUTPUT);
            canvas.toBlob(function (blob) {
                var file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                if (!setInputFile(file)) { modal.hidden = true; show(original, 'Browser tidak mendukung', true); return; }
                committed = file;
                remove.value = '0';
                modal.hidden = true;
                show(URL.createObjectURL(blob), 'Belum disimpan');
            }, 'image/jpeg', 0.9);
        });

        // ---------- Tombol Edit / Trash ----------
        root.querySelector('[data-avatar-edit]').addEventListener('click', function () { input.click(); });

        input.addEventListener('change', function () {
            var f = input.files[0];
            if (!f) { setInputFile(committed); return; }
            var msg = !/^image\/(jpeg|png)$/.test(f.type) ? 'Harus JPG/PNG'
                    : f.size > MAX_SOURCE ? 'Foto terlalu besar' : '';
            if (msg) {
                setInputFile(committed);
                caption.textContent = msg;
                caption.classList.add('text-red-600');
                caption.classList.remove('text-blue-600');
                return;
            }
            openCropper(f);
        });

        trash.addEventListener('click', function () {
            committed = null;
            setInputFile(null);
            remove.value = original ? '1' : '0';
            show(null, original ? 'Dihapus saat disimpan' : '');
        });
    })();
</script>
