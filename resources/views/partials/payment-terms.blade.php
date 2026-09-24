{{-- Pemilih termin pembayaran (2026-09-24, feedback user): klien kadang
     membagi pembayaran jadi 3 tahap. Staf memilih jumlah tahap dulu, lalu
     persentasenya terisi otomatis dan boleh diubah. Nilai dikirim ke server
     lewat input tersembunyi "payment_terms" berformat lama ("50,50"), jadi
     validasi & parser di ProposalController tidak berubah.

     Dibutuhkan: $termPercents (array persen tersimpan), $locked (bool). --}}
@php
    $fmt   = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $terms = array_values(array_map($fmt, $termPercents ?: [50, 50]));
    $terms = count($terms) > 3 ? array_slice($terms, 0, 3) : $terms;
    $stage = max(1, min(3, count($terms)));
@endphp

<div class="sm:col-span-2">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Termin Pembayaran
            @include('partials.icon-info', ['tip' => 'Pilih berapa kali klien membayar. 1 tahap = lunas di awal. 2 tahap = DP lalu pelunasan. 3 tahap = persentasenya diisi sendiri. Jumlah seluruh termin harus 100%.'])
        </label>
        <span id="pt_sum_badge" class="rounded-full px-2.5 py-0.5 text-xs font-semibold"></span>
    </div>

    {{-- Pilihan jumlah tahap: tombol segmen, bukan dropdown, supaya semua
         pilihan terlihat sekaligus. --}}
    <div id="pt_stage_group" class="mt-2 inline-flex rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-600 dark:bg-gray-900/60" role="group" aria-label="Jumlah tahap pembayaran">
        @foreach ([1 => '1 Tahap', 2 => '2 Tahap', 3 => '3 Tahap'] as $n => $label)
            <button type="button" data-stage="{{ $n }}" @disabled($locked ?? false)
                    class="pt-stage rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 transition disabled:cursor-not-allowed disabled:opacity-60 dark:text-gray-300">
                {{ $label }}
            </button>
        @endforeach
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
    const buttons = [...document.querySelectorAll('.pt-stage')];

    const ON  = 'bg-blue-600 text-white shadow-sm';
    const OFF = 'text-gray-600 dark:text-gray-300';

    let values = (hidden.value || '50,50').split(',')
        .map(v => parseFloat(String(v).replace(',', '.')))
        .filter(v => v > 0);
    if (!values.length) values = [50, 50];
    if (values.length > 3) values = values.slice(0, 3);

    let total = 0;   // total biaya jasa, diisi recalcFeeTotal() lewat window.ptSetTotal

    const round2   = n => Math.round(n * 100) / 100;
    const trimNum  = n => String(round2(n)).replace('.', ',');
    const rupiah   = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    function paint() {
        buttons.forEach(b => {
            const active = +b.dataset.stage === values.length;
            b.className = 'pt-stage rounded-md px-3 py-1.5 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-60 ' + (active ? ON : OFF);
            b.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        rowsBox.innerHTML = values.map((v, i) => `
            <div class="flex items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm text-gray-600 dark:text-gray-300">${LABELS[values.length][i]}</div>
                    {{-- Layar sempit: nominal pindah ke bawah label supaya labelnya tidak terpotong. --}}
                    <div class="text-xs tabular-nums text-gray-400 sm:hidden dark:text-gray-500" data-amount-sm="${i}"></div>
                </div>
                <div class="relative w-24 shrink-0">
                    <input type="text" inputmode="decimal" data-i="${i}" value="${trimNum(v)}" ${LOCKED || values.length === 1 ? 'disabled' : ''}
                           class="pt-pct w-full rounded-md border-gray-300 py-1.5 pr-7 text-right text-sm tabular-nums shadow-sm disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-600 dark:bg-gray-900 dark:disabled:bg-gray-900/60">
                    <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-sm text-gray-400">%</span>
                </div>
                <span class="hidden w-32 shrink-0 text-right text-sm tabular-nums text-gray-500 sm:block dark:text-gray-400" data-amount="${i}"></span>
            </div>`).join('');

        rowsBox.querySelectorAll('.pt-pct').forEach(input => {
            input.addEventListener('input', onType);
            input.addEventListener('blur', onBlur);
        });

        sync();
    }

    /** Mengetik satu termin: sisanya dibagi rata ke termin lain supaya total tetap 100. */
    function onType(e) {
        const i = +e.target.dataset.i;
        const typed = parseFloat(String(e.target.value).replace(',', '.'));
        if (!isFinite(typed) || typed < 0) { sync(); return; }

        values[i] = Math.min(typed, 100);
        if (typed > 100) e.target.value = trimNum(values[i]);   // tidak boleh lewat 100%
        const others = values.map((_, k) => k).filter(k => k !== i);
        const rest   = round2(100 - values[i]);

        if (others.length === 1) {
            values[others[0]] = Math.max(0, rest);
        } else if (others.length > 1) {
            // Bagi sisa mengikuti proporsi lama supaya angka yang sudah diatur
            // staf tidak lompat jauh; kalau lamanya nol, bagi rata.
            const oldSum = others.reduce((s, k) => s + values[k], 0);
            others.forEach((k, idx) => {
                values[k] = oldSum > 0 ? round2(rest * values[k] / oldSum) : round2(rest / others.length);
                if (idx === others.length - 1) {
                    values[k] = round2(rest - others.slice(0, -1).reduce((s, kk) => s + values[kk], 0));
                }
            });
        }

        // Tulis ulang kolom lain saja — kolom yang sedang diketik jangan diganggu.
        rowsBox.querySelectorAll('.pt-pct').forEach(input => {
            const k = +input.dataset.i;
            if (k !== i) input.value = trimNum(values[k]);
        });
        sync();
    }

    function onBlur(e) {
        const i = +e.target.dataset.i;
        e.target.value = trimNum(values[i]);
        sync();
    }

    /** Perbarui badge total, nominal rupiah, dan input tersembunyi. */
    function sync() {
        const sum    = round2(values.reduce((s, v) => s + v, 0));
        const noZero = values.every(v => v > 0);
        const ok     = Math.abs(sum - 100) < 0.01 && noZero;

        badge.textContent = 'Total ' + trimNum(sum) + '%';
        badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-semibold ' + (ok
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
            : 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300');

        values.forEach((v, i) => {
            const text = total > 0 ? rupiah(total * v / 100) : '';
            rowsBox.querySelectorAll(`[data-amount="${i}"], [data-amount-sm="${i}"]`)
                .forEach(cell => { cell.textContent = text; });
        });

        hint.textContent = LOCKED
            ? 'Terkunci — proyek sudah masuk tahap pencetakan buku.'
            : ok
            ? (values.length === 1
                ? 'Klien membayar sekali, lunas di awal.'
                : 'Angka di kolom lain menyesuaikan sendiri supaya totalnya tetap 100%.')
            : (noZero
                ? 'Jumlah termin sekarang ' + trimNum(sum) + '%. Harus tepat 100% sebelum proposal disimpan.'
                : 'Setiap termin harus lebih dari 0%. Kurangi jumlah tahap kalau termin ini tidak dipakai.');
        hint.className = 'mt-2 text-xs ' + (ok ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400');

        hidden.value = values.map(v => String(round2(v))).join(',');
    }

    buttons.forEach(btn => btn.addEventListener('click', () => {
        if (LOCKED) return;
        const n = +btn.dataset.stage;
        if (n === values.length) return;
        values = DEFAULTS[n].slice();
        paint();
    }));

    // Dipanggil recalcFeeTotal() supaya nominal per termin ikut berubah.
    window.ptSetTotal = function (t) { total = +t || 0; sync(); };

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
            rowsBox.querySelector('.pt-pct:not([disabled])')?.focus();
        }, true);
    }

    paint();
})();
</script>
