{{-- Notifikasi per tombol alur proyek (2026-09-22, feedback user): aktif/tidak,
     siapa yang di-mention, grup tujuan, dan isi pesan. Butuh $notifications,
     $groups (cache daftar grup), $users. --}}
@php
    use App\Models\Project;
    use App\Models\User;
    use App\Models\WhatsAppNotification;

    $noNumber = $users->filter(fn ($u) => blank($u->whatsapp_number)
        && in_array($u->jabatan, [User::JABATAN_PELAKSANA_INSPEKSI, User::JABATAN_PENILAI, User::JABATAN_REVIEWER, User::JABATAN_ADMIN], true));
    $groupName = fn ($jid) => $groups->firstWhere('id', $jid)['subject'] ?? $jid;
    $recipientLabel = function (array $recipients) use ($users) {
        return collect($recipients)->map(fn ($r) => match (true) {
            $r === 'reviewers'  => 'Reviewer',
            $r === 'appraisers' => 'Penilai lapangan',
            $r === 'submitter'  => 'Pengaju review',
            str_starts_with($r, 'jabatan:') => 'Semua ' . substr($r, 8),
            str_starts_with($r, 'user:')    => $users->firstWhere('id', (int) substr($r, 5))?->name,
            default => null,
        })->filter()->implode(', ') ?: 'tanpa mention';
    };
    $field = 'rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600 dark:bg-gray-900';
@endphp

<div id="notifikasi" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
        <div class="min-w-0">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Notifikasi per Tombol</h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Klik tombol untuk mengatur pesan, penerima mention, dan grup tujuannya.</p>
        </div>
        @if ($noNumber->isNotEmpty())
            {{-- Ringkas: jumlah saja, nama muncul saat diklik. --}}
            <details class="group relative">
                <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800">
                    <svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    {{ $noNumber->count() }} pengguna belum punya Nomor WhatsApp
                </summary>
                <div class="absolute right-0 z-20 mt-2 w-72 rounded-md border border-gray-200 bg-white p-3 text-xs shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-gray-500 dark:text-gray-400">Tanpa nomor, nama hanya ditulis (tidak di-mention). Klik nama untuk melengkapi:</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($noNumber as $u)
                            <a href="{{ route('users.edit', $u) }}" class="rounded bg-gray-100 px-2 py-0.5 text-gray-700 hover:bg-blue-50 hover:text-blue-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-blue-900/30">{{ $u->name }}</a>
                        @endforeach
                    </div>
                </div>
            </details>
        @endif
    </div>

    <form action="{{ route('settings.whatsapp.notifications') }}" method="POST" class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-700 dark:border-gray-700">
        @csrf
        @method('PUT')

        @foreach ($notifications as $step => $n)
            @php
                $def   = Project::WORKFLOW_STEPS[$step];
                $recip = (array) old("n.$step.recipients", $n->recipients ?? []);
                $jab   = collect($recip)->filter(fn ($r) => str_starts_with($r, 'jabatan:'))->map(fn ($r) => substr($r, 8))->all();
                $picked = collect($recip)->filter(fn ($r) => str_starts_with($r, 'user:'))->map(fn ($r) => (int) substr($r, 5))->all();
                $jid   = old("n.$step.group_jid", $n->group_jid);
            @endphp
            <details class="group" @if ($errors->has("n.$step.*")) open @endif>
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                    <svg aria-hidden="true" class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $def['title'] }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">tombol &ldquo;{{ $def['button'] }}&rdquo;</span>
                    <span class="ml-auto flex items-center gap-2">
                        <span class="hidden text-xs text-gray-400 sm:inline dark:text-gray-500">{{ $recipientLabel((array) $n->recipients) }}</span>
                        @if ($n->enabled)
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">Aktif</span>
                        @else
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">Nonaktif</span>
                        @endif
                    </span>
                </summary>

                <div class="grid grid-cols-1 gap-5 bg-gray-50/60 px-5 py-4 md:grid-cols-2 dark:bg-gray-900/30">
                    {{-- Kiri: aktif, penerima, grup --}}
                    <div class="space-y-4 text-sm">
                        <label class="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="n[{{ $step }}][enabled]" value="1" class="rounded border-gray-300" @checked(old("n.$step.enabled", $n->enabled))>
                            Kirim pesan saat tombol ini ditekan
                        </label>

                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300">Mention</p>
                            <div class="mt-1.5 space-y-1.5">
                                @foreach (WhatsAppNotification::RECIPIENT_GROUPS as $key => $label)
                                    <label class="flex items-start gap-2 text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" name="n[{{ $step }}][recipients][]" value="{{ $key }}" class="mt-0.5 rounded border-gray-300" @checked(in_array($key, $recip, true))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-3 text-xs font-medium text-gray-500 dark:text-gray-400">Semua pengguna aktif berjabatan:</p>
                            <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1.5">
                                @foreach (User::JABATAN_OPTIONS as $j)
                                    <label class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" name="n[{{ $step }}][jabatan][]" value="{{ $j }}" class="rounded border-gray-300" @checked(in_array($j, $jab, true))>
                                        {{ $j }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-3 text-xs font-medium text-gray-500 dark:text-gray-400">Orang tertentu:</p>
                            <div class="mt-1.5 max-h-36 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2 dark:border-gray-700">
                                @foreach ($users as $u)
                                    <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" name="n[{{ $step }}][users][]" value="{{ $u->id }}" class="rounded border-gray-300" @checked(in_array($u->id, $picked, true))>
                                        <span class="truncate">{{ $u->name }}</span>
                                        @if (blank($u->whatsapp_number))<span class="shrink-0 text-[10px] text-amber-600 dark:text-amber-400">tanpa nomor</span>@endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="font-medium text-gray-700 dark:text-gray-300" for="grp_{{ $step }}">Grup tujuan</label>
                            <select id="grp_{{ $step }}" name="n[{{ $step }}][group_jid]" class="mt-1 w-full {{ $field }}">
                                <option value="">Grup utama</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g['id'] }}" @selected($jid === $g['id'])>{{ $g['subject'] }}</option>
                                @endforeach
                                @if ($jid && ! $groups->contains('id', $jid))
                                    <option value="{{ $jid }}" selected>{{ $jid }}</option>
                                @endif
                            </select>
                            @error("n.$step.group_jid") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Kanan: isi pesan + pratinjau --}}
                    <div class="space-y-2 text-sm">
                        <label class="font-medium text-gray-700 dark:text-gray-300" for="tpl_{{ $step }}">Isi pesan</label>
                        <textarea id="tpl_{{ $step }}" name="n[{{ $step }}][template]" rows="10" data-wa-template
                                  class="w-full font-mono text-xs {{ $field }}">{{ old("n.$step.template", $n->template) }}</textarea>
                        @error("n.$step.template") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex flex-wrap gap-1">
                            @foreach (WhatsAppNotification::PLACEHOLDERS as $ph => $tip)
                                <button type="button" title="{{ $tip }}" data-wa-insert="{{ $ph }}" data-target="tpl_{{ $step }}"
                                        class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-600 hover:bg-blue-50 hover:text-blue-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-blue-900/30">{{ $ph }}</button>
                            @endforeach
                        </div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pratinjau (contoh):</p>
                        <pre data-wa-preview="tpl_{{ $step }}" data-title="{{ $def['title'] }}"
                             class="whitespace-pre-wrap rounded-md bg-[#E7FFDB] p-3 font-sans text-xs text-gray-800 dark:bg-emerald-950/40 dark:text-gray-200"></pre>
                        <button type="submit" form="wa_test_{{ $step }}" @disabled(! $configured)
                                class="text-xs font-medium text-blue-600 hover:text-blue-800 disabled:cursor-not-allowed disabled:opacity-50 dark:text-blue-400">
                            Kirim tes ke grup (pengaturan yang sudah disimpan)
                        </button>
                    </div>
                </div>
            </details>
        @endforeach

        <div class="flex justify-end px-5 py-4">
            <button type="submit" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-blue-600 bg-blue-600 px-4 text-sm font-medium text-white transition hover:border-blue-700 hover:bg-blue-700">Simpan Notifikasi</button>
        </div>
    </form>

    @foreach ($notifications as $step => $n)
        <form id="wa_test_{{ $step }}" action="{{ route('settings.whatsapp.notifications.test', $step) }}" method="POST" class="hidden">@csrf</form>
    @endforeach
</div>

<script>
(function () {
    // Contoh nilai untuk pratinjau; di pesan asli diisi data proyek.
    const sample = {
        '{nomor_proposal}': '00246/2.0131-00/KJPPSPR-PRO/APP/IX/2026',
        '{klien}': 'PT Contoh Klien',
        '{pemberi_tugas}': 'PT Bank Contoh',
        '{oleh}': @json(auth()->user()->name),
        '{catatan}': 'Contoh catatan.',
        '{tahap}': 'Penyusunan draft laporan',
        '{mention}': '@Reviewer @Penilai',
        '{link}': @json(url('/proposals/1')),
    };
    function render(pre) {
        const ta = document.getElementById(pre.dataset.waPreview);
        let text = ta.value;
        const map = Object.assign({ '{judul}': pre.dataset.title }, sample);
        Object.keys(map).forEach(k => { text = text.split(k).join(map[k]); });
        pre.textContent = text.trim();
    }
    document.querySelectorAll('[data-wa-preview]').forEach(pre => {
        render(pre);
        document.getElementById(pre.dataset.waPreview).addEventListener('input', () => render(pre));
    });
    document.querySelectorAll('[data-wa-insert]').forEach(btn => btn.addEventListener('click', () => {
        const ta = document.getElementById(btn.dataset.target);
        const pos = ta.selectionStart ?? ta.value.length;
        ta.value = ta.value.slice(0, pos) + btn.dataset.waInsert + ta.value.slice(ta.selectionEnd ?? pos);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = pos + btn.dataset.waInsert.length;
        ta.dispatchEvent(new Event('input'));
    }));
})();
</script>
