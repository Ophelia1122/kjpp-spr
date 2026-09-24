{{-- Pemilih termin pembayaran (2026-09-24, feedback user): klien kadang
     membagi pembayaran jadi 3 tahap. Staf memilih jumlah tahap dulu, lalu
     persentasenya terisi otomatis dan boleh diubah — lewat persen ATAU lewat
     nominal rupiah. Nilai dikirim ke server lewat input tersembunyi
     "payment_terms" berformat lama ("50,50"), jadi validasi & parser di
     ProposalController tidak berubah.

     Dibutuhkan: $termPercents (array persen tersimpan), $locked (bool). --}}
@php
    $fmt   = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $terms = array_values(array_map($fmt, $termPercents ?: [50, 50]));
    $terms = count($terms) > 3 ? array_slice($terms, 0, 3) : $terms;
@endphp

<div class="sm:col-span-2">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Termin Pembayaran
            @include('partials.icon-info', ['tip' => 'Pilih berapa kali klien membayar, lalu isi pembagiannya — boleh lewat persen atau langsung nominal rupiah. Kolom termin terakhir menyesuaikan sendiri supaya totalnya pas 100%.'])
        </label>
        <span id="pt_sum_badge" class="rounded-full px-2.5 py-0.5 text-xs font-semibold"></span>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-2">
        {{-- Pilihan jumlah tahap: tombol segmen, bukan dropdown, supaya semua
             pilihan terlihat sekaligus. --}}
        <div id="pt_stage_group" class="inline-flex rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-600 dark:bg-gray-900/60" role="group" aria-label="Jumlah tahap pembayaran">
            @foreach ([1 => '1 Tahap', 2 => '2 Tahap', 3 => '3 Tahap'] as $n => $label)
                <button type="button" data-stage="{{ $n }}" @disabled($locked ?? false)
                        class="pt-stage rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 transition disabled:cursor-not-allowed disabled:opacity-60 dark:text-gray-300">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Isi pakai persen atau nominal — hasilnya sama, disimpan tetap persen. --}}
        <div id="pt_mode_group" class="inline-flex rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-600 dark:bg-gray-900/60" role="group" aria-label="Cara mengisi termin">
            @foreach (['persen' => 'Persen', 'nominal' => 'Nominal'] as $key => $label)
                <button type="button" data-mode="{{ $key }}" @disabled($locked ?? false)
                        class="pt-mode rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 transition disabled:cursor-not-allowed disabled:opacity-60 dark:text-gray-300">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div id="pt_rows" class="mt-3 space-y-2"></div>

    <p id="pt_hint" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></p>
    <input type="hidden" name="payment_terms" id="paymentTerms" value="{{ old('payment_terms', implode(',', $terms)) }}">
    @error('payment_terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<script>
(function () {
    const LOCKED   = @json((bool) ($locked ?? false));
    const DEFAULTS = { 1: [100], 2: [50, 50], 3: [40, 30, 30] };
    const LABELS   = {
        1: ['Pelunasan (100%)'],
        2: ['Termin 1 — DP', 'Termin 2 — Pelunasan'],
        3: ['Termin 1 — DP', 'Termin 2', 'Termin 3 — Pelunasan'],
    };

    const hidden  = document.getElementById('paymentTerms');
    const rowsBox = document.getElementById('pt_rows');
    const badge   = document.getElementById('pt_sum_badge');
    const hint    = document.getElementById('pt_hint');
    const stageBtns = [...document.querySelectorAll('.pt-stage')];
    const modeBtns  = [...document.querySelectorAll('.pt-mode')];

    const ON  = 'bg-blue-600 text-white shadow-sm';
    const OFF = 'text-gray-600 dark:text-gray-300';

    let values = (hidden.value || '50,50').split(',')
        .map(v => parseFloat(String(v).replace(',', '.')))
        .filter(v => v > 0);
    if (!values.length) values = [50, 50];
    if (values.length > 3) values = values.slice(0, 3);

    let mode  = 'persen';
    let total = 0;   // total biaya jasa, diisi recalcFeeTotal() lewat window.ptSetTotal

    const round2  = n => Math.round(n * 100) / 100;
    const trimNum = n => String(round2(n)).replace('.', ',');
    const rupiah  = n => Math.round(n).toLocaleString('id-ID');
    const digits  = s => String(s).replace(/[^\d]/g, '');

    /**
     * Nominal per termin. Termin penyeimbang mengambil sisa pembulatan supaya
     * jumlah seluruh termin persis sama dengan total biaya — rumus yang sama
     * dipakai ProposalDocxBuilder saat mencetak proposal.
     */
    function amounts() {
        const last = values.length - 1;
        let sisa = total;
        return values.map((v, i) => {
            if (i === last) return sisa;
            const a = Math.round(total * v / 100);
            sisa -= a;
            return a;
        });
    }

    function paint() {
        stageBtns.forEach(b => {
            const active = +b.dataset.stage === values.length;
            b.className = 'pt-stage rounded-md px-3 py-1.5 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-60 ' + (active ? ON : OFF);
            b.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        modeBtns.forEach(b => {
            const active = b.dataset.mode === mode;
            b.className = 'pt-mode rounded-md px-3 py-1.5 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-60 ' + (active ? ON : OFF);
            b.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        const amt     = amounts();
        const oneOnly = values.length === 1;
        const dead    = LOCKED || oneOnly || (mode === 'nominal' && total <= 0);

        rowsBox.innerHTML = values.map((v, i) => `
            <div class="flex items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm text-gray-600 dark:text-gray-300">${LABELS[values.length][i]}</div>
                    {{-- Layar sempit: nilai pasangannya pindah ke bawah label supaya label tidak terpotong. --}}
                    <div class="text-xs tabular-nums text-gray-400 sm:hidden dark:text-gray-500" data-echo-sm="${i}"></div>
                </div>
                <div class="relative ${mode === 'nominal' ? 'w-40' : 'w-24'} shrink-0">
                    ${mode === 'nominal' ? '<span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-sm text-gray-400">Rp</span>' : ''}
                    <input type="text" inputmode="decimal" data-i="${i}" ${dead ? 'disabled' : ''}
                           value="${mode === 'nominal' ? rupiah(amt[i]) : trimNum(v)}"
                           class="pt-in w-full rounded-md border-gray-300 py-1.5 text-right text-sm tabular-nums shadow-sm disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-600 dark:bg-gray-900 dark:disabled:bg-gray-900/60 ${mode === 'nominal' ? 'pl-9 pr-3' : 'pr-7'}">
                    ${mode === 'nominal' ? '' : '<span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-sm text-gray-400">%</span>'}
                </div>
                <span class="hidden w-32 shrink-0 text-right text-sm tabular-nums text-gray-500 sm:block dark:text-gray-400" data-echo="${i}"></span>
            </div>`).join('');

        rowsBox.querySelectorAll('.pt-in').forEach(input => {
            input.addEventListener('input', onType);
            input.addEventListener('blur', onBlur);
        });

        sync();
    }

    /**
     * Kolom mana yang menyerap sisa. Biasanya termin TERAKHIR; kalau yang
     * sedang diketik justru termin terakhir, sisanya jatuh ke termin
     * sebelumnya. Hanya satu kolom yang ikut berubah, jadi angka yang sudah
     * diketik staf tidak berubah jadi pecahan (2026-09-24, feedback user:
     * "mau dibulatin jadi 15 15 70 susah, ada buntut sekian di belakang").
     */
    function balanceIndex(edited) {
        const last = values.length - 1;
        return edited === last ? last - 1 : last;
    }

    function onType(e) {
        const i = +e.target.dataset.i;
        if (values.length < 2) return;

        let pct;
        if (mode === 'nominal') {
            if (total <= 0) return;
            const amt = parseInt(digits(e.target.value) || '0', 10);
            e.target.value = rupiah(amt);              // rapikan titik ribuan sambil mengetik
            pct = round2(Math.min(amt, total) / total * 100);
        } else {
            const typed = parseFloat(String(e.target.value).replace(',', '.'));
            if (!isFinite(typed) || typed < 0) { sync(); return; }
            pct = round2(Math.min(typed, 100));
            if (typed > 100) e.target.value = trimNum(pct);   // tidak boleh lewat 100%
        }

        values[i] = pct;

        const b = balanceIndex(i);
        const lainnya = values.reduce((s, v, k) => k === b ? s : s + v, 0);
        values[b] = Math.max(0, round2(100 - lainnya));

        // Tulis ulang kolom penyeimbang saja — kolom yang sedang diketik jangan diganggu.
        const amt = amounts();
        rowsBox.querySelectorAll('.pt-in').forEach(input => {
            const k = +input.dataset.i;
            if (k !== i) input.value = mode === 'nominal' ? rupiah(amt[k]) : trimNum(values[k]);
        });
        sync();
    }

    function onBlur(e) {
        const i = +e.target.dataset.i;
        e.target.value = mode === 'nominal' ? rupiah(amounts()[i]) : trimNum(values[i]);
        sync();
    }

    /** Perbarui badge total, nilai pasangan tiap baris, dan input tersembunyi. */
    function sync() {
        const sum    = round2(values.reduce((s, v) => s + v, 0));
        const noZero = values.every(v => v > 0);
        const ok     = Math.abs(sum - 100) < 0.01 && noZero;

        badge.textContent = 'Total ' + trimNum(sum) + '%';
        badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-semibold ' + (ok
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
            : 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300');

        // Kolom kanan menampilkan satuan yang TIDAK sedang diketik: kalau isian
        // memakai persen, di sebelahnya tampil rupiah, dan sebaliknya.
        const amt = amounts();
        values.forEach((v, i) => {
            const text = mode === 'nominal'
                ? trimNum(v) + '%'
                : (total > 0 ? 'Rp ' + rupiah(amt[i]) : '');
            rowsBox.querySelectorAll(`[data-echo="${i}"], [data-echo-sm="${i}"]`)
                .forEach(cell => { cell.textContent = text; });
        });

        hint.textContent = LOCKED
            ? 'Terkunci — proyek sudah masuk tahap pencetakan buku.'
            : (mode === 'nominal' && total <= 0)
            ? 'Isi Nilai Biaya Jasa dulu supaya termin bisa diisi dalam rupiah.'
            : ok
            ? (values.length === 1
                ? 'Klien membayar sekali, lunas di awal.'
                : 'Termin terakhir menyesuaikan sendiri supaya totalnya pas 100%. Ketik angka bulat, hasilnya tetap bulat.')
            : (noZero
                ? 'Jumlah termin sekarang ' + trimNum(sum) + '%. Harus tepat 100% sebelum proposal disimpan.'
                : 'Setiap termin harus lebih dari 0%. Kurangi jumlah tahap kalau termin ini tidak dipakai.');
        hint.className = 'mt-2 text-xs ' + ((ok || LOCKED) ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400');

        hidden.value = values.map(v => String(round2(v))).join(',');
    }

    stageBtns.forEach(btn => btn.addEventListener('click', () => {
        if (LOCKED) return;
        const n = +btn.dataset.stage;
        if (n === values.length) return;
        values = DEFAULTS[n].slice();
        paint();
    }));

    modeBtns.forEach(btn => btn.addEventListener('click', () => {
        if (LOCKED || btn.dataset.mode === mode) return;
        mode = btn.dataset.mode;
        paint();
    }));

    // Dipanggil recalcFeeTotal() supaya nominal per termin ikut berubah.
    window.ptSetTotal = function (t) {
        const berubah = (+t || 0) !== total;
        total = +t || 0;
        if (berubah && mode === 'nominal') { paint(); return; }   // isian rupiah ikut total baru
        sync();
    };

    // Tahan penyimpanan kalau termin belum sah — pesannya sudah tampil di
    // bawah kolom, jadi cukup diarahkan ke sana.
    const form = document.getElementById('proposalForm');
    if (form && !LOCKED) {
        form.addEventListener('submit', function (e) {
            const sum = round2(values.reduce((s, v) => s + v, 0));
            if (Math.abs(sum - 100) < 0.01 && values.every(v => v > 0)) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            rowsBox.scrollIntoView({ block: 'center', behavior: 'smooth' });
            rowsBox.querySelector('.pt-in:not([disabled])')?.focus();
        }, true);
    }

    paint();
})();
</script>
