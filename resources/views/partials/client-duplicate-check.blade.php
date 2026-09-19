{{-- Peringatan klien kembar di modal "Tambah Klien Baru" (2026-09-15, feedback user).
     Dipasang tepat setelah kolom Alamat. Butuh fungsi halaman: applyModalClient(client)
     & closeClientModal(). Tidak memblokir penyimpanan — hanya saran. --}}
<div id="clientDupBox" class="hidden rounded-md border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-700 dark:bg-amber-900/20">
    <p class="font-medium text-amber-800 dark:text-amber-300">Klien serupa sudah ada</p>
    <p class="text-xs text-amber-700 dark:text-amber-400">Pakai klien yang sudah ada supaya tidak dobel, atau tetap buat baru bila memang berbeda.</p>
    <ul id="clientDupList" class="mt-2 max-h-56 space-y-2 overflow-y-auto"></ul>
    <button type="button" onclick="clientDupDismiss()" class="mt-2 text-xs font-medium text-amber-800 underline hover:no-underline dark:text-amber-300">Tetap buat baru</button>
</div>
<script>
    (function () {
        const url = @json(route('clients.similar'));
        const nameInput = document.getElementById('modal_client_name');
        const addressInput = document.getElementById('modal_client_address');
        const box = document.getElementById('clientDupBox');
        const list = document.getElementById('clientDupList');
        let timer = null;
        let lastKey = '';
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        window.clientDupMatches = [];
        window.clientDupDismissed = false;
        window.clientDupReset = function () {
            window.clientDupMatches = [];
            window.clientDupDismissed = false;
            lastKey = '';
            list.innerHTML = '';
            box.classList.add('hidden');
        };
        window.clientDupDismiss = function () {
            window.clientDupDismissed = true;
            box.classList.add('hidden');
        };

        function check() {
            const name = nameInput.value.trim();
            const address = addressInput.value.trim();
            if (name.length < 3) { window.clientDupReset(); return; }
            const key = name + '|' + address;
            if (key === lastKey) return;
            lastKey = key;
            window.clientDupDismissed = false;

            fetch(url + '?' + new URLSearchParams({ name, address }), { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((matches) => {
                    window.clientDupMatches = matches;
                    if (!matches.length) { box.classList.add('hidden'); return; }
                    list.innerHTML = matches.map((c, i) => {
                        const tag = c.same_address
                            ? '<span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Nama &amp; alamat sama</span>'
                            : (c.address_checked
                                ? '<span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Alamat berbeda — cabang lain?</span>'
                                : '<span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nama mirip</span>');
                        return `<li class="rounded-md border border-amber-200 bg-white p-2 dark:border-amber-800 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">${esc(c.client_name)}</p>
                                    <p class="text-xs text-gray-500 line-clamp-2 dark:text-gray-400">${esc(c.address) || '(tanpa alamat)'}</p>
                                    <div class="mt-1">${tag}</div>
                                </div>
                                <button type="button" data-dup-index="${i}" class="shrink-0 rounded-md bg-blue-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-700">Pakai klien ini</button>
                            </div>
                        </li>`;
                    }).join('');
                    box.classList.remove('hidden');
                })
                .catch(() => {});
        }

        const schedule = () => { clearTimeout(timer); timer = setTimeout(check, 500); };
        nameInput.addEventListener('input', schedule);
        addressInput.addEventListener('input', schedule);

        list.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-dup-index]');
            if (!btn) return;
            const c = window.clientDupMatches[+btn.dataset.dupIndex];
            applyModalClient({ id: c.id, client_name: c.client_name, address: c.address || '' });
            closeClientModal();
        });
    })();
</script>
