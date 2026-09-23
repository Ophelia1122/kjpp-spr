{{-- Tanda Terima Pengiriman Buku (2026-09-23, feedback user). Tampil mulai
     tahap pengiriman buku sampai proyek selesai. Butuh $project & $clients. --}}
@php
    use App\Models\DeliveryReceipt;
    use App\Models\Project;

    $receipts = $project->deliveryReceipts;
    $canManage = Project::userCanActAs(auth()->user(), 'admin_or_keuangan');
    $fld = 'mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900';
    // Keterangan baku mengikuti contoh kantor.
    $noteDefault = 'Laporan Penilaian an ' . $project->effective_client_name
        . ($project->final_report_number ? ' dengan No. Laporan ' . $project->final_report_number : '')
        . ' berikut Kwitansi, Invoice dan Faktur Pajak';
@endphp

<div id="section-tanda-terima" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-4">
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tanda Terima Pengiriman Buku</h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Bukti serah terima buku laporan &amp; dokumen pendukung ke klien. Boleh lebih dari satu.</p>
        </div>
        @if ($receipts->isNotEmpty())
            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $receipts->count() }} tanda terima</span>
        @endif
    </div>

    @if ($receipts->isNotEmpty())
        <div class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-700 dark:border-gray-700">
            @foreach ($receipts as $receipt)
                <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $receipt->number }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $receipt->delivery_date->translatedFormat('d F Y') }} &middot;
                            {{ $receipt->recipient?->client_name ?: $project->effective_client_name }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                            @foreach ($receipt->documentRows() as $row)
                                {{ $row['label'] }} ({{ $row['qty'] }} {{ $row['unit'] }}){{ ! $loop->last ? ' · ' : '' }}
                            @endforeach
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('receipts.pdf', [$project, $receipt]) }}"
                           class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">PDF</a>
                        <a href="{{ route('receipts.word', [$project, $receipt]) }}"
                           class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Word</a>
                        @if ($canManage)
                            <form action="{{ route('receipts.destroy', [$project, $receipt]) }}" method="POST"
                                  data-confirm="Hapus Tanda Terima {{ $receipt->number }}? Nomor yang sudah terpakai tidak dipakai ulang.">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($canManage)
        <form action="{{ route('receipts.store', $project) }}" method="POST"
              class="space-y-4 border-t border-gray-100 bg-gray-50/60 px-5 py-4 dark:border-gray-700 dark:bg-gray-900/30">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Tanda Terima</label>
                    <input type="text" value="{{ $nextReceiptNumber }}" disabled
                           title="Nomor dibuat otomatis saat disimpan"
                           class="{{ $fld }} bg-gray-100 text-gray-500 dark:bg-gray-800">
                </div>
                <div>
                    <label for="tt_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pengiriman</label>
                    <input type="date" id="tt_date" name="delivery_date" lang="id" required
                           value="{{ old('delivery_date', now()->toDateString()) }}" class="{{ $fld }}">
                    @error('delivery_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tt_up" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Up. (opsional)</label>
                    <input type="text" id="tt_up" name="recipient_up" value="{{ old('recipient_up') }}"
                           placeholder="Nama orang yang dituju" class="{{ $fld }}">
                </div>
            </div>

            {{-- Penerima: pencarian klien + chip nama & alamat, pola sama dengan
                 Pemberi Tugas di form proposal (2026-09-23, feedback user). --}}
            <div>
                <label for="tt_client_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Penerima</label>
                <div class="mt-1 flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" id="tt_client_search" autocomplete="off"
                               placeholder="Ketik nama klien untuk mencari… (kosong = klien proyek ini)"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900">
                        <div id="tt_client_results"
                             class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"></div>
                    </div>
                    <button type="button" onclick="ttOpenClientModal()"
                            class="inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700">
                        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Klien Baru
                    </button>
                </div>
                <input type="hidden" name="recipient_client_id" id="tt_client_id" value="{{ old('recipient_client_id') }}">
                <div id="tt_client_chip" style="display:none"
                     class="mt-2 items-start justify-between gap-3 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    <div class="min-w-0">
                        <div id="tt_client_chip_name" class="font-medium"></div>
                        <div id="tt_client_chip_address" class="whitespace-pre-line text-xs opacity-80"></div>
                    </div>
                    <button type="button" onclick="ttClearClient()" class="text-blue-500 hover:text-blue-700 dark:text-blue-400">&times;</button>
                </div>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Kosong = memakai nama &amp; alamat klien proyek ini.</p>
                @error('recipient_client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Rincian dokumen</p>
                <div class="mt-1.5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach (DeliveryReceipt::DOCUMENT_TYPES as $key => [$label, $unit])
                        <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                            <input type="checkbox" id="tt_chk_{{ $key }}" class="rounded border-gray-300"
                                   onchange="document.getElementById('tt_qty_{{ $key }}').value = this.checked ? 1 : 0"
                                   @checked(old('documents.' . $key))>
                            <label for="tt_chk_{{ $key }}" class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</label>
                            <input type="number" id="tt_qty_{{ $key }}" name="documents[{{ $key }}]" min="0" max="999" step="1"
                                   value="{{ old('documents.' . $key, 0) }}"
                                   class="w-16 rounded-md border-gray-300 text-sm shadow-sm tabular-nums dark:border-gray-600 dark:bg-gray-900">
                            <span class="w-12 text-xs text-gray-400 dark:text-gray-500">{{ $unit }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Jenis dokumen selalu tercetak &ldquo;Asli&rdquo;. Jumlah 0 = tidak ikut dikirim.</p>
                @error('documents') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="tt_note" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                <textarea id="tt_note" name="note" rows="2" class="{{ $fld }}">{{ old('note', $noteDefault) }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex h-[38px] items-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                    Simpan Tanda Terima
                </button>
            </div>
        </form>
    @endif
</div>

@if ($canManage)
    {{-- Modal "Klien Baru" khusus kartu ini (dipindah ke <body> lewat skrip
         supaya tidak terpengaruh animasi <main>). --}}
    <div id="ttClientModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true"
         onclick="if (event.target === this) ttCloseClientModal()">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tambah Klien Baru</h2>
                <button type="button" onclick="ttCloseClientModal()" aria-label="Tutup" class="text-xl leading-none text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
            </div>
            <div id="ttClientModalErrors" class="mb-3 hidden text-sm text-red-600 dark:text-red-400"></div>
            <div class="space-y-3">
                <div>
                    <label for="tt_modal_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Klien</label>
                    <input type="text" id="tt_modal_name" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                </div>
                <div>
                    <label for="tt_modal_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Klien</label>
                    <select id="tt_modal_type" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                        <option value="Perbankan">Perbankan</option>
                        <option value="Korporat">Korporat</option>
                        <option value="Perorangan">Perorangan</option>
                    </select>
                </div>
                <div>
                    <label for="tt_modal_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat</label>
                    <textarea id="tt_modal_address" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900"></textarea>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="ttCloseClientModal()"
                        class="inline-flex h-[38px] items-center rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                <button type="button" onclick="ttSubmitNewClient()"
                        class="inline-flex h-[38px] items-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const modal = document.getElementById('ttClientModal');
        document.body.appendChild(modal);

        const searchUrl = @json(route('clients.search'));
        const storeUrl  = @json(route('clients.storeAjax'));
        const csrf      = document.querySelector('meta[name="csrf-token"]')?.content;
        const input     = document.getElementById('tt_client_search');
        const results   = document.getElementById('tt_client_results');

        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        window.ttSetClient = function (client) {
            document.getElementById('tt_client_id').value = client.id;
            document.getElementById('tt_client_chip_name').textContent = client.client_name;
            document.getElementById('tt_client_chip_address').textContent = client.address || 'Alamat belum diisi';
            document.getElementById('tt_client_chip').style.display = 'flex';
            input.value = '';
            input.classList.add('hidden');
            results.classList.add('hidden');
        };
        window.ttClearClient = function () {
            document.getElementById('tt_client_id').value = '';
            document.getElementById('tt_client_chip').style.display = 'none';
            input.classList.remove('hidden');
        };
        window.ttOpenClientModal = function () {
            document.getElementById('ttClientModalErrors').classList.add('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('tt_modal_name').focus();
        };
        window.ttCloseClientModal = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };
        window.ttSubmitNewClient = async function () {
            const errors = document.getElementById('ttClientModalErrors');
            const payload = {
                client_name: document.getElementById('tt_modal_name').value.trim(),
                client_type: document.getElementById('tt_modal_type').value,
                address: document.getElementById('tt_modal_address').value.trim(),
            };
            try {
                const res = await fetch(storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(Object.values(data.errors ?? { m: [data.message ?? 'Gagal menyimpan klien.'] }).flat().join(' '));
                ttSetClient(data);
                ttCloseClientModal();
            } catch (e) {
                errors.textContent = e.message;
                errors.classList.remove('hidden');
            }
        };

        let timer;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            const keyword = this.value.trim();
            if (keyword.length < 2) { results.classList.add('hidden'); return; }
            timer = setTimeout(async () => {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(keyword)}`, { headers: { 'Accept': 'application/json' } });
                const clients = await res.json();
                results.innerHTML = clients.length
                    ? clients.map((c, i) => `
                        <button type="button" data-i="${i}" class="w-full border-b border-gray-100 px-3 py-2 text-left text-sm last:border-0 hover:bg-blue-50 dark:border-gray-800 dark:hover:bg-blue-900/30">
                            <div class="font-medium text-gray-800 dark:text-gray-200">${esc(c.client_name)}</div>
                            <div class="whitespace-pre-line text-xs text-gray-400 dark:text-gray-500">${esc(c.address) || 'Alamat belum diisi'}</div>
                        </button>`).join('')
                    : '<div class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">Tidak ditemukan. Coba &ldquo;Klien Baru&rdquo;.</div>';
                results.querySelectorAll('button[data-i]').forEach(btn => btn.addEventListener('click', () => ttSetClient(clients[+btn.dataset.i])));
                results.classList.remove('hidden');
            }, 300);
        });
        document.addEventListener('click', e => {
            if (!results.contains(e.target) && e.target !== input) results.classList.add('hidden');
        });
    })();
    </script>
@endif
