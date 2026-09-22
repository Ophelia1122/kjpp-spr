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
    {{-- Modal pengaturan koneksi --}}
    <div id="wa_conn_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="wa_conn_title"
         onclick="if (event.target === this) waConnModal(false)">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="wa_conn_title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pengaturan Koneksi Bot</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Sambungan ke Evolution API dan grup utama.</p>
                </div>
                <button type="button" onclick="waConnModal(false)" aria-label="Tutup" class="text-xl leading-none text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
            </div>
        <form action="{{ route('settings.whatsapp.update') }}" method="POST" id="wa_conn_form" class="space-y-4">
            @csrf
            @method('PUT')

            <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="enabled" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked(old('enabled', filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN)))>
                <span class="font-medium">Aktifkan notifikasi WhatsApp</span>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Evolution API</label>
                <input type="url" name="base_url" autocomplete="off"
                       value="{{ old('base_url', $settings['base_url']) }}"
                       placeholder="Contoh: http://kjpp-wa:8080"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono dark:border-gray-600">
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Di NAS: <code>http://kjpp-wa:8080</code>. Di laptop: <code>http://localhost:8081</code>.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
                <input type="password" name="api_key" autocomplete="new-password"
                       placeholder="{{ $settings['api_key'] ? '•••••••• (tersimpan — kosongkan bila tidak diubah)' : 'AUTHENTICATION_API_KEY dari .env bot' }}"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Instance</label>
                <input type="text" name="instance" autocomplete="off"
                       value="{{ old('instance', $settings['instance']) }}"
                       placeholder="kjpp"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Nama instance yang dibuat & di-scan QR-nya di dashboard Evolution API.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grup Utama</label>
                <div class="mt-1 flex flex-wrap gap-2">
                    <input type="text" name="group_jid" id="wa_group_jid" autocomplete="off"
                           value="{{ old('group_jid', $settings['group_jid']) }}"
                           placeholder="1203630xxxxxxxxx@g.us"
                           class="min-w-0 flex-1 rounded-md border-gray-300 shadow-sm font-mono text-sm dark:border-gray-600">
                    <button type="button" id="wa_fetch_groups"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
                        Ambil daftar grup
                    </button>
                </div>
                <select id="wa_group_select" style="display:none"
                        class="mt-2 w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600"></select>
                <p id="wa_group_msg" class="mt-1 text-xs text-gray-400 dark:text-gray-500">Simpan URL, API key & instance dulu, lalu ambil daftar grup yang diikuti nomor bot.</p>
                @error('group_jid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                <button type="button" onclick="waConnModal(false)" class="inline-flex h-[38px] items-center rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-4 text-sm font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">Simpan</button>
                <button type="submit" form="wa_test_form" @disabled(! $configured)
                        class="px-5 py-2 border border-gray-300 rounded-md hover:bg-gray-50 font-medium text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700/60 dark:text-gray-300">
                    Kirim Pesan Tes
                </button>
            </div>
        </form>
        </div>
    </div>




    <form id="wa_test_form" action="{{ route('settings.whatsapp.test') }}" method="POST">@csrf</form>

    @include('settings._whatsapp_notifications')
</div>

<script>
document.getElementById('wa_fetch_groups').addEventListener('click', async function () {
    const msg = document.getElementById('wa_group_msg');
    const select = document.getElementById('wa_group_select');
    const input = document.getElementById('wa_group_jid');
    msg.textContent = 'Mengambil daftar grup…';
    try {
        const res = await fetch(@json(route('settings.whatsapp.groups')), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Gagal mengambil daftar grup.');
        if (!data.groups.length) { msg.textContent = 'Nomor bot belum bergabung di grup mana pun.'; return; }
        select.innerHTML = '<option value="">-- Pilih grup --</option>' + data.groups.map(g =>
            `<option value="${g.id}" ${g.id === input.value ? 'selected' : ''}>${g.subject.replace(/</g, '&lt;')}</option>`).join('');
        select.style.display = '';
        msg.textContent = data.groups.length + ' grup ditemukan. Pilih grup, lalu klik Simpan.';
    } catch (e) {
        msg.textContent = e.message;
    }
});
document.getElementById('wa_group_select').addEventListener('change', function () {
    if (this.value) document.getElementById('wa_group_jid').value = this.value;
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
