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

            <div>
                <div class="flex items-end justify-between gap-2">
                    <label for="tt_recipient" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Penerima</label>
                    <a href="{{ route('clients.create') }}" target="_blank" rel="noopener"
                       class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">+ Klien baru</a>
                </div>
                <select id="tt_recipient" name="recipient_client_id" class="{{ $fld }}">
                    <option value="">— Pemberi Tugas / Pengguna Laporan —</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('recipient_client_id') == $client->id)>{{ $client->client_name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Kosong = memakai nama klien proyek ini.</p>
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
