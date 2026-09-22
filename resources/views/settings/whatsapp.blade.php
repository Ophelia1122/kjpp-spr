@extends('layouts.app')

@section('title', 'Bot WhatsApp')

@section('content')
{{-- Header selebar halaman seperti menu lain (2026-09-20, feedback user);
     isi form tetap sempit supaya enak dibaca. --}}
<div class="max-w-7xl mx-auto py-8 space-y-6">
    <x-page-header title="Bot WhatsApp" subtitle="Pesan otomatis ke grup WhatsApp kantor saat tombol alur proyek ditekan.">
        @if ($configured)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Tersambung &amp; aktif
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Nonaktif
            </span>
        @endif
        {{-- Pengaturan koneksi bot dipindah ke modal (2026-09-22, feedback user). --}}
        <button type="button" onclick="waConnModal(true)" title="Pengaturan koneksi bot" aria-label="Pengaturan koneksi bot"
                class="grid h-[38px] w-[38px] place-items-center rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
            <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            </svg>
        </button>
    </x-page-header>
    {{-- Modal pengaturan koneksi (didesain ulang 2026-09-22). Dipindah ke <body>
         lewat JS di bawah: di dalam <main> ada animasi transform yang membuat
         position:fixed ikut elemen induk sehingga modal tidak tampil. --}}
    @php
        $inp = 'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
        $lbl = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
        $hint = 'mt-1 text-xs text-gray-500 dark:text-gray-400';
        $groupsCache = collect(json_decode((string) \App\Models\AppSetting::getValue('wa_groups_cache'), true) ?: []);
        $curJid = old('group_jid', $settings['group_jid']);
        $enabledNow = (bool) old('enabled', filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN));
    @endphp
    <div id="wa_conn_modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="wa_conn_title"
         onclick="if (event.target === this) waConnModal(false)">
        <form action="{{ route('settings.whatsapp.update') }}" method="POST" id="wa_conn_form"
              class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-800">
            @csrf
            @method('PUT')

            {{-- Header --}}
            <div class="flex items-start gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                    <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.87 9.87 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2Zm5.8 14.12c-.24.68-1.41 1.3-1.95 1.35-.5.05-.97.23-3.28-.68-2.78-1.1-4.54-3.95-4.68-4.13-.14-.18-1.12-1.49-1.12-2.84s.71-2.02.96-2.29c.25-.27.55-.34.73-.34h.52c.17 0 .4-.06.62.47.24.56.8 1.93.87 2.07.07.14.12.3.02.48-.09.18-.14.3-.28.46-.14.16-.29.36-.42.48-.14.14-.28.29-.12.56.16.27.72 1.18 1.54 1.91 1.06.94 1.95 1.23 2.23 1.37.27.14.43.12.59-.07.16-.18.68-.8.86-1.07.18-.27.36-.23.61-.14.25.09 1.59.75 1.86.89.27.14.46.2.52.32.07.11.07.66-.17 1.33Z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="wa_conn_title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Pengaturan Koneksi Bot</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sambungan ke Evolution API dan grup utama WhatsApp.</p>
                </div>
                <button type="button" onclick="waConnModal(false)" aria-label="Tutup"
                        class="grid h-8 w-8 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Isi --}}
            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                {{-- Sakelar aktif --}}
                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">Aktifkan notifikasi</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Matikan untuk menghentikan semua pesan bot sementara.</span>
                    </span>
                    <input type="checkbox" name="enabled" value="1" class="peer sr-only" @checked($enabledNow)>
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition peer-checked:bg-green-500 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5 dark:bg-gray-600"></span>
                </label>

                <div>
                    <label for="wa_base_url" class="{{ $lbl }}">URL Evolution API</label>
                    <input type="url" id="wa_base_url" name="base_url" autocomplete="off" value="{{ old('base_url', $settings['base_url']) }}"
                           placeholder="http://kjpp-wa:8080" class="{{ $inp }}">
                    <p class="{{ $hint }}">NAS: <span class="font-medium">http://kjpp-wa:8080</span> &middot; Laptop: <span class="font-medium">http://localhost:8081</span></p>
                    @error('base_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="wa_api_key" class="{{ $lbl }}">API Key</label>
                        <input type="password" id="wa_api_key" name="api_key" autocomplete="new-password"
                               placeholder="{{ $settings['api_key'] ? '•••••••• tersimpan' : 'WA_BOT_API_KEY' }}" class="{{ $inp }}">
                        <p class="{{ $hint }}">{{ $settings['api_key'] ? 'Kosongkan bila tidak diganti.' : 'Nilai WA_BOT_API_KEY dari .env.' }}</p>
                    </div>
                    <div>
                        <label for="wa_instance" class="{{ $lbl }}">Instance</label>
                        <input type="text" id="wa_instance" name="instance" autocomplete="off" value="{{ old('instance', $settings['instance']) }}"
                               placeholder="kjpp" class="{{ $inp }}">
                    </div>
                </div>

                <div>
                    <div class="flex items-end justify-between gap-2">
                        <label for="wa_group_select" class="{{ $lbl }}">Grup utama</label>
                        <button type="button" id="wa_fetch_groups" class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            <svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            Muat ulang daftar grup
                        </button>
                    </div>
                    {{-- Nilai yang dikirim = ID grup (hidden); pilihan tampil dengan nama grup. --}}
                    <input type="hidden" name="group_jid" id="wa_group_jid" value="{{ $curJid }}">
                    <select id="wa_group_select" class="{{ $inp }}">
                        <option value="">— Pilih grup —</option>
                        @foreach ($groupsCache as $g)
                            <option value="{{ $g['id'] }}" @selected($curJid === $g['id'])>{{ $g['subject'] }}</option>
                        @endforeach
                        @if ($curJid && ! $groupsCache->contains('id', $curJid))
                            <option value="{{ $curJid }}" selected>{{ $curJid }}</option>
                        @endif
                    </select>
                    <p id="wa_group_msg" class="{{ $hint }}">
                        {{ $groupsCache->isEmpty() ? 'Simpan URL, API key & instance dulu, lalu klik "Muat ulang daftar grup".' : 'Grup yang diikuti nomor bot. Tiap tombol bisa memakai grup lain di Notifikasi per Tombol.' }}
                    </p>
                    @error('group_jid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                <button type="submit" form="wa_test_form" @disabled(! $configured)
                        title="{{ $configured ? 'Kirim pesan tes ke grup utama' : 'Simpan & aktifkan dulu' }}"
                        class="inline-flex h-[38px] items-center gap-1.5 rounded-md px-3 text-sm font-medium text-green-700 hover:bg-green-50 disabled:cursor-not-allowed disabled:opacity-40 dark:text-green-400 dark:hover:bg-green-900/30">
                    <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                    Kirim pesan tes
                </button>
                <div class="flex gap-2">
                    <button type="button" onclick="waConnModal(false)"
                            class="inline-flex h-[38px] items-center rounded-md border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Batal</button>
                    <button type="submit"
                            class="inline-flex h-[38px] items-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
                </div>
            </div>
        </form>
    </div>

    <form id="wa_test_form" action="{{ route('settings.whatsapp.test') }}" method="POST">@csrf</form>

    @include('settings._whatsapp_notifications')
</div>

<script>
// Modal dipindah ke <body> supaya position:fixed menutupi layar penuh.
document.body.appendChild(document.getElementById('wa_conn_modal'));

document.getElementById('wa_group_select').addEventListener('change', function () {
    document.getElementById('wa_group_jid').value = this.value;
});
document.getElementById('wa_fetch_groups').addEventListener('click', async function () {
    const msg = document.getElementById('wa_group_msg');
    const select = document.getElementById('wa_group_select');
    const current = document.getElementById('wa_group_jid').value;
    msg.textContent = 'Mengambil daftar grup…';
    try {
        const res = await fetch(@json(route('settings.whatsapp.groups')), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Gagal mengambil daftar grup.');
        if (!data.groups.length) { msg.textContent = 'Nomor bot belum bergabung di grup mana pun.'; return; }
        const esc = t => t.replace(/&/g, '&amp;').replace(/</g, '&lt;');
        select.innerHTML = '<option value="">— Pilih grup —</option>' + data.groups.map(g =>
            `<option value="${esc(g.id)}" ${g.id === current ? 'selected' : ''}>${esc(g.subject)}</option>`).join('');
        msg.textContent = data.groups.length + ' grup ditemukan. Pilih grup, lalu klik Simpan.';
    } catch (e) {
        msg.textContent = e.message;
    }
});
function waConnModal(open) {
    const m = document.getElementById('wa_conn_modal');
    m.classList.toggle('hidden', !open);
    m.classList.toggle('flex', open);
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') waConnModal(false); });
// Buka otomatis bila koneksi belum lengkap atau isian koneksi ditolak.
@if (! $configured || $errors->hasAny(['base_url', 'api_key', 'instance', 'group_jid']))
    waConnModal(true);
@endif
</script>
@endsection
