@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    @php
        // Badge SLA ringkas & eye-catching, langsung terlihat begitu halaman
        // dibuka (tanpa perlu scroll ke kartu SLA Draft/Final yang detail di
        // kartu "Aksi Tersedia"). Fase AKTIF: Final kalau sudah dikonfirmasi
        // Admin Produksi, kalau belum ya Draft. Null = belum relevan (belum
        // ada penilai/tanggal survei, atau proyek sudah Selesai/Batal).
        $activeSla = null;
        if ($project->assigned_appraiser && $project->survey_date
            && !$project->isCancelled() && $project->status !== \App\Models\Project::STATUS_SELESAI) {
            $activeSla = ($project->isReviewApproved() && $project->estimated_final_completion_date)
                ? ['label' => 'SLA Laporan Final', 'state' => $project->final_sla_state, 'text' => $project->final_sla_label, 'target' => $project->estimated_final_completion_date_formatted]
                : ['label' => 'SLA Draft/Resume', 'state' => $project->sla_state, 'text' => $project->sla_label, 'target' => $project->estimated_completion_date_formatted];
        }
        $slaBadgeTone = [
            'overdue'  => 'bg-rose-100 text-rose-700 border-rose-300 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800',
            'due-soon' => 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
            'on-track' => 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
            'none'     => 'bg-gray-100 text-gray-500 border-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600',
        ];
    @endphp
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 break-words">{{ $project->proposal_number }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Tanggal proposal: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $project->effective_proposal_date->translatedFormat('d F Y') }}</span>
                <span class="text-gray-400 dark:text-gray-500">&middot; input {{ $project->created_at->translatedFormat('d F Y') }}</span>
            </p>
            {{-- Badge SLA dipindah ke ujung kanan bar navigasi cepat
                 (2026-09-14, feedback user — sebagai kotak besar di sini
                 terasa mengganjal & memotong hierarki header). Di bar nav
                 dia ikut sticky, jadi malah selalu terlihat saat scroll. --}}
            {{-- "Batalkan Project"/"Aktifkan Kembali" dipindah jadi ikon di
                 kanan status badge (2026-09-14, feedback user — dulu numpuk
                 vertikal dengan Edit Bab Proposal, terasa ganjal). Info
                 tanggal dibatalkan pindah ke sini juga, jadi bagian dari
                 meta info tanggal proyek. --}}
            @if ($project->isCancelled())
                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    Dibatalkan {{ optional($project->cancelled_at)->translatedFormat('d F Y') ?? '—' }}
                    @if ($project->status_before_cancel)
                        · sebelumnya: {{ $project->status_before_cancel }}
                    @endif
                </p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold {{ $project->status_badge_classes }}">
                {{ $project->status }}
            </span>
            @can('proposals.manage')
                @if ($project->isCancelled())
                    <form action="{{ route('proposals.reactivate', $project) }}" method="POST"
                          onsubmit="return confirm('Aktifkan kembali proyek {{ $project->proposal_number }}? Status akan kembali ke: {{ $project->status_before_cancel ?: \App\Models\Project::STATUS_DRAFT }}.')">
                        @csrf
                        <button type="submit" title="Aktifkan Kembali"
                                class="grid h-8 w-8 place-items-center rounded-md text-gray-400 hover:bg-emerald-100 hover:text-emerald-700 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-300">
                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                            </svg>
                        </button>
                    </form>
                @else
                    <form action="{{ route('proposals.cancel', $project) }}" method="POST"
                          onsubmit="return confirm('Batalkan proyek {{ $project->proposal_number }}? Data TIDAK dihapus — status menjadi Batal dan bisa diaktifkan kembali kapan saja.')">
                        @csrf
                        <button type="submit" title="Batalkan Project"
                                class="grid h-8 w-8 place-items-center rounded-md text-gray-400 hover:bg-rose-100 hover:text-rose-700 dark:hover:bg-rose-900/30 dark:hover:text-rose-300">
                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 1 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    {{-- ===================== NAVIGASI CEPAT (QUICK NAV) =====================
         Pill tab bar sticky (2026-09-14, feedback user — Opsi A dari 2
         alternatif yang diajukan). JS di bawah otomatis membuang pill yang
         section-nya tidak dirender (izin/status tidak cocok) supaya tidak
         pernah ada link mati, dan menandai pill aktif sesuai section yang
         sedang terlihat (scrollspy sederhana pakai IntersectionObserver). --}}
    <div class="sticky top-14 lg:top-3 z-10 flex items-center gap-2 bg-white border border-gray-200 rounded-lg p-1.5 shadow-sm dark:bg-gray-800 dark:border-gray-700">
    <div id="quickNav" class="flex min-w-0 flex-1 gap-1.5 overflow-x-auto">
        <a href="#section-info" data-target="section-info" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Info</a>
        <a href="#section-penilai" data-target="section-penilai" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Penilai</a>
        <a href="#section-surat-tugas" data-target="section-surat-tugas" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Surat Tugas</a>
        <a href="#section-tagihan" data-target="section-tagihan" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Tagihan</a>
        <a href="#section-faktur" data-target="section-faktur" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Faktur</a>
        <a href="#section-laporan-resmi" data-target="section-laporan-resmi" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Laporan Resmi</a>
        <a href="#section-aksi" data-target="section-aksi" class="quicknav-pill shrink-0 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">Catatan</a>
    </div>
        @if ($activeSla)
            <span id="quickNavSla"
                  title="{{ $activeSla['label'] }} · Target: {{ $activeSla['target'] ?? '—' }}"
                  class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full border text-xs font-semibold whitespace-nowrap {{ $slaBadgeTone[$activeSla['state']] ?? $slaBadgeTone['none'] }}">
                <span class="leading-none">⏱</span>
                <span>{{ $activeSla['text'] }}</span>
            </span>
        @endif
    </div>
    <script>
        (function () {
            function initQuickNav() {
                var nav = document.getElementById('quickNav');
                if (!nav) return;
                var links = Array.from(nav.querySelectorAll('[data-target]'));
                var sections = [];
                links.forEach(function (link) {
                    var el = document.getElementById(link.dataset.target);
                    if (!el) { link.remove(); return; }
                    sections.push({ el: el, id: link.dataset.target });
                });
                if (sections.length === 0) {
                    // Tidak ada section sama sekali: buang seluruh bar, kecuali
                    // masih ada badge SLA di dalamnya (bar tetap berguna).
                    var wrap = nav.parentElement;
                    if (wrap && !wrap.querySelector('#quickNavSla')) { wrap.remove(); }
                    else { nav.remove(); }
                    return;
                }

                function setActive(id) {
                    links.forEach(function (link) {
                        var active = link.dataset.target === id;
                        link.classList.toggle('bg-blue-600', active);
                        link.classList.toggle('text-white', active);
                        link.classList.toggle('text-gray-500', !active);
                        link.classList.toggle('dark:text-gray-400', !active);
                    });
                }

                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) setActive(entry.target.id);
                    });
                }, { rootMargin: '-96px 0px -70% 0px', threshold: 0 });

                sections.forEach(function (s) { observer.observe(s.el); });
                setActive(sections[0].id);
            }
            {{-- Script ini sengaja ditaruh SEBELUM kartu-kartu lain di HTML
                 (letaknya persis di bawah nav-nya, biar dekat) — tapi kartu
                 section-nya baru di-parse browser SETELAH baris ini, jadi
                 WAJIB nunggu DOMContentLoaded, bukan langsung jalan (2026-09-14,
                 dites: getElementById selalu null kalau dijalankan langsung). --}}
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initQuickNav);
            } else {
                initQuickNav();
            }
        })();
    </script>

    {{-- ===================== CARD INFORMASI UTAMA ===================== --}}
    <div id="section-info" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Informasi Proyek</h2>
            <div class="flex items-center gap-1">
                @can('proposals.manage')
                    @if ($project->status !== \App\Models\Project::STATUS_SELESAI)
                        <a href="{{ route('proposals.edit', $project) }}" title="Edit Proposal"
                           class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                            @include('partials.icon-pencil')
                        </a>
                    @endif
                    {{-- "Edit Bab Proposal" dipindah dari header halaman ke
                         sini (2026-09-14, feedback user) — sama-sama aksi
                         edit konten proposal, lebih pas dikelompokkan
                         dengan ikon Edit Proposal daripada berdiri sendiri
                         di banner atas. --}}
                    <a href="{{ route('proposals.texts', $project) }}" title="Edit Bab Proposal{{ ($project->section_texts_count ?? 0) > 0 ? ' (' . $project->section_texts_count . ' bab diedit)' : '' }}"
                       class="relative grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        @if (($project->section_texts_count ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 h-4 min-w-4 px-0.5 rounded-full bg-amber-500 text-white text-[10px] leading-4 text-center dark:bg-amber-600">{{ $project->section_texts_count }}</span>
                        @endif
                    </a>
                @endcan
                <button type="button" id="infoProjectToggle" title="Sembunyikan/Tampilkan Informasi Proyek"
                        class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg id="infoProjectEyeIcon" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    <svg id="infoProjectEyeSlashIcon" hidden class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>
        </div>

        <dl id="infoProjectBody" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div class="sm:col-span-2">
                <dt class="text-gray-500 dark:text-gray-400">Nama Klien</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $project->effective_client_name }}
                    @unless (trim((string) $project->client_name))
                        <span class="text-gray-400 font-normal dark:text-gray-500" title="Nama Klien belum diisi — memakai nama Pemberi Tugas.">(= Pemberi Tugas)</span>
                    @endunless
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-gray-500 dark:text-gray-400">Pemberi Tugas</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $project->instructingClient->client_name }}
                    <span class="text-gray-400 font-normal dark:text-gray-500">({{ $project->instructingClient->client_type }})</span>
                </dd>
                <dd class="text-gray-600 text-xs mt-0.5 whitespace-pre-line dark:text-gray-400">{{ $project->instructingClient->address ?: '(alamat belum diisi pada data klien)' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-gray-500 dark:text-gray-400">Pengguna Laporan</dt>
                <dd class="font-medium text-gray-900 mt-1 space-y-1 dark:text-gray-100">
                    @foreach ($project->intendedUsers as $user)
                        <div class="text-xs bg-gray-50 border border-gray-200 rounded-md px-2 py-1.5 dark:bg-gray-900 dark:border-gray-700">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $loop->iteration }}. {{ $user->client_name }}</span>
                            <span class="text-gray-400 font-normal dark:text-gray-500">({{ $user->client_type }})</span>
                            <span class="text-gray-500 dark:text-gray-400">— {{ $user->address ?: '(alamat belum diisi pada data klien)' }}</span>
                        </div>
                    @endforeach
                </dd>
            </div>
            {{-- Jenis Aset & Lokasi Aset dibuang dari sini (2026-09-14,
                 feedback user) — sudah dicakup per-objek di bagian
                 "Objek Penilaian" di bawah, jadi dobel kalau ditampilkan
                 di sini juga. Kolom asset_type/asset_address di database
                 TETAP dipakai (ringkasan dipakai PDF/dashboard/tabel),
                 cuma tidak ditampilkan lagi di kartu ini. --}}

            {{-- ===================== RINCIAN OBJEK PENILAIAN ===================== --}}
            @if ($project->valuationObjects && $project->valuationObjects->isNotEmpty())
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">Objek Penilaian ({{ $project->valuationObjects->count() }})</dt>
                    <dd class="font-medium text-gray-900 mt-1 space-y-2 dark:text-gray-100">
                        @foreach ($project->valuationObjects as $object)
                            <div class="text-xs bg-gray-50 border border-gray-200 rounded-md p-2 dark:bg-gray-900 dark:border-gray-700">
                                <div class="font-semibold text-gray-700 dark:text-gray-300">{{ $loop->iteration }}. {{ $object->short_label }}</div>
                                <div class="text-gray-500 dark:text-gray-400">{{ $object->location }}</div>
                                <div class="text-gray-500 dark:text-gray-400">Hak: {{ $object->ownership_form }} – a.n. {{ $object->owner_name }}</div>
                            </div>
                        @endforeach
                    </dd>
                </div>
            @endif

            <div class="sm:col-span-2">
                <dt class="text-gray-500 dark:text-gray-400">Jenis Laporan</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->report_style_label }}</dd>
                <dd class="text-gray-500 text-xs mt-0.5 dark:text-gray-400">
                    SLA Laporan Draft/Resume:
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $project->sla_draft_days ? $project->sla_draft_days . ' hari kerja' : '—' }}</span>
                    &nbsp;·&nbsp;
                    SLA Laporan Final:
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $project->sla_final_days ? $project->sla_final_days . ' hari kerja' : '—' }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Biaya Jasa</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">Rp {{ number_format($project->total_fee, 0, ',', '.') }}</dd>
                <dd class="text-gray-500 text-xs mt-0.5 dark:text-gray-400">
                    Fee Rp {{ number_format($project->fee_professional, 0, ',', '.') }}
                    + PPN Rp {{ number_format($project->fee_ppn_amount, 0, ',', '.') }}
                    @if (($project->transport_cost ?? 0) > 0)
                        + Transport Rp {{ number_format($project->fee_transport_display, 0, ',', '.') }}
                    @endif
                    · {{ $project->fee_ppn_included ? 'sudah termasuk PPN' : 'PPN ditambahkan atas Fee & transport' }}
                    @if ($project->transport_reimbursed) · transport &amp; akomodasi ditanggung klien (reimburse) @endif
                    @if ($project->fee_breakdown) · ditampilkan sebagai rincian @endif
                </dd>
            </div>
            <div>
                {{-- Caption sekunder ("biodata dari akun pengguna" / "belum
                     dipilih di proposal") dibuang (2026-09-14, feedback
                     user) — cuma catatan asal data, tidak perlu selalu
                     tampil. Jabatan/gelar penandatangan tetap ditampilkan
                     karena itu info substantif, bukan catatan provenance. --}}
                <dt class="text-gray-500 dark:text-gray-400">Penanggung Jawab</dt>
                @if ($project->signedBy)
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->signedBy->name }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5 dark:text-gray-400">
                        {{ $project->signedBy->partner_status ?: config('kjpp.signatory.title') }}
                    </dd>
                @else
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ config('kjpp.signatory.name') }}</dd>
                @endif
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Pihak yang Menyetujui</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->effective_approver_name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Marketing</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->marketing_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Rekening Pembayaran</dt>
                @if ($project->bank)
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->bank->bank_name }}{{ $project->bank->branch ? ' (' . $project->bank->branch . ')' : '' }} — {{ $project->bank->account_number }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5 dark:text-gray-400">a.n. {{ $project->bank->account_name }} · dipilih di proposal</dd>
                @elseif ($default = \App\Models\Bank::default())
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $default->bank_name }}{{ $default->branch ? ' (' . $default->branch . ')' : '' }} — {{ $default->account_number }}</dd>
                    {{-- "· rekening default kantor" dibuang (2026-09-14,
                         feedback user) — qualifier lain (dipilih di
                         proposal / dari config) tetap dipertahankan. --}}
                    <dd class="text-gray-500 text-xs mt-0.5 dark:text-gray-400">a.n. {{ $default->account_name }}</dd>
                @else
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ config('kjpp.bank_account.bank_name') }} — {{ config('kjpp.bank_account.account_number') }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5 dark:text-gray-400">dari config (belum ada master rekening)</dd>
                @endif
            </div>
        </dl>

        <script>
            (function () {
                var btn = document.getElementById('infoProjectToggle');
                var body = document.getElementById('infoProjectBody');
                var eyeIcon = document.getElementById('infoProjectEyeIcon');
                var eyeSlashIcon = document.getElementById('infoProjectEyeSlashIcon');
                if (!btn || !body) return;
                btn.addEventListener('click', function () {
                    body.hidden = !body.hidden;
                    eyeIcon.hidden = body.hidden;
                    eyeSlashIcon.hidden = !body.hidden;
                });
            })();
        </script>
    </div>

    {{-- ===================== CARD PENILAI LAPANGAN & TANGGAL SURVEI =====================
         Assign siapa yang turun lapangan + kapan, dengan pola kunci/edit yang
         sama seperti Faktur Pajak/Surat Tugas — dipindah jadi kartu tersendiri
         (sebelumnya nempel di blok status In-Progress, jadi hilang dari
         tampilan begitu status proyek berubah lagi). Bisa diisi/diedit di
         status apa pun selama proyek belum Selesai/Batal (lihat guard
         sungguhannya di ProjectController@inputSurveyData). --}}
    @canany(['survey.manage', 'survey.view'])
        <div id="section-penilai" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
            @php $hasSurvey = $project->assigned_appraiser_id && $project->survey_date; @endphp

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Penilai Lapangan &amp; Tanggal Survei</h2>
                @can('survey.manage')
                    <div class="flex items-center gap-1">
                        @if ($hasSurvey)
                            <button type="button" id="surveyEditBtn" title="Edit"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                @include('partials.icon-pencil')
                            </button>
                            <button type="submit" form="surveyForm" id="surveySaveBtn" hidden title="Simpan"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                @include('partials.icon-check')
                            </button>
                        @else
                            <button type="submit" form="surveyForm" title="Simpan Jadwal Survei"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                @include('partials.icon-check')
                            </button>
                        @endif
                    </div>
                @endcan
            </div>

            @can('survey.manage')
                {{-- Daftar Penilai Lapangan HANYA akun berperan Administrator atau
                     Surveyor (2026-09-13, feedback user) — Admin Keuangan/Produksi
                     tidak relevan turun lapangan jadi tidak perlu muncul di sini.
                     Label pakai jabatan (biodata), bukan role, supaya lebih relevan
                     (Penilai/Pelaksana Inspeksi) daripada nama role sistem. --}}
                @php
                    $fieldAppraiserOptions = $activeUsers->filter(
                        fn ($u) => in_array($u->role?->slug, [\App\Models\Role::ADMINISTRATOR, \App\Models\Role::SURVEYOR], true)
                    );
                @endphp
                <form action="{{ route('projects.inputSurveyData', $project) }}" method="POST" id="surveyForm">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nama Penilai Lapangan
                                <span class="inline-block align-text-bottom text-gray-400 dark:text-gray-500" title="Daftar ini menampilkan semua pengguna aktif, pilih akun Surveyor yang benar-benar turun lapangan karena mengikat pada Timeline Proyek.">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                                    </svg>
                                </span>
                            </label>
                            <select name="assigned_appraiser_id" id="survey_appraiser" required @disabled($hasSurvey)
                                    @if ($hasSurvey) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                                <option value="">-- Pilih Penilai --</option>
                                @foreach ($fieldAppraiserOptions as $appraiserOption)
                                    <option value="{{ $appraiserOption->id }}" @selected(old('assigned_appraiser_id', $project->assigned_appraiser_id) == $appraiserOption->id)>
                                        {{ $appraiserOption->name }} ({{ $appraiserOption->jabatan ?: '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Survei</label>
                            <input type="date" name="survey_date" id="survey_date" required @disabled($hasSurvey) lang="id"
                                   @if ($hasSurvey) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                   value="{{ old('survey_date', $project->survey_date?->toDateString()) }}"
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                        </div>
                    </div>
                </form>
                @if ($hasSurvey)
                    <script>
                        (function () {
                            var b = document.getElementById('surveyEditBtn');
                            if (!b) return;
                            b.addEventListener('click', function () {
                                ['survey_appraiser', 'survey_date'].forEach(function (id) {
                                    document.getElementById(id).disabled = false;
                                    document.getElementById(id).removeAttribute('title');
                                });
                                document.getElementById('survey_appraiser').focus();
                                b.hidden = true;
                                document.getElementById('surveySaveBtn').hidden = false;
                            });
                        })();
                    </script>
                @endif
            @else
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Penilai Lapangan</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->assigned_appraiser ?: '(belum diisi)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal Survei</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->survey_date?->translatedFormat('d F Y') ?: '(belum diisi)' }}</dd>
                    </div>
                </dl>
            @endcan
        </div>
    @endcanany

    {{-- ===================== CARD SURAT TUGAS =====================
         Nomor/Tanggal (kunci setelah diisi, sama seperti Faktur Pajak) +
         upload barcode verifikasi (PNG 370x370, maks 5KB) + daftar
         petugas bebas (jumlah & jabatan apa saja — dicetak di tabel
         "Adapun petugas kami") + tombol cetak PDF-nya. --}}
    @canany(['assignment_letter.manage', 'survey.view'])
        <div id="section-surat-tugas" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
            @php $hasSurat = $project->assignment_letter_number || $project->assignment_letter_date; @endphp

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Surat Tugas</h2>
                @can('assignment_letter.manage')
                    <div class="flex items-center gap-1">
                        @if ($hasSurat)
                            <button type="button" id="suratEditBtn" title="Edit"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                @include('partials.icon-pencil')
                            </button>
                            <button type="submit" form="suratTugasForm" id="suratSaveBtn" hidden title="Simpan"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                @include('partials.icon-check')
                            </button>
                        @else
                            <button type="submit" form="suratTugasForm" title="Simpan"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                @include('partials.icon-check')
                            </button>
                        @endif
                    </div>
                @endcan
            </div>

            @can('assignment_letter.manage')
                <form action="{{ route('projects.updateAssignmentLetter', $project) }}" method="POST" enctype="multipart/form-data" id="suratTugasForm">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Surat Tugas</label>
                            <input type="text" name="assignment_letter_number" id="surat_number"
                                   value="{{ old('assignment_letter_number', $project->assignment_letter_number) }}"
                                   placeholder="Contoh: 1570/KJPPSPR-ST/VIII/2026"
                                   @disabled($hasSurat)
                                   @if ($hasSurat) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Surat Tugas</label>
                            <input type="date" name="assignment_letter_date" id="surat_date" lang="id"
                                   value="{{ old('assignment_letter_date', $project->assignment_letter_date?->toDateString()) }}"
                                   @disabled($hasSurat)
                                   @if ($hasSurat) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                        </div>
                    </div>
                </form>
                @if ($hasSurat)
                    <script>
                        (function () {
                            var b = document.getElementById('suratEditBtn');
                            if (!b) return;
                            b.addEventListener('click', function () {
                                ['surat_number', 'surat_date'].forEach(function (id) {
                                    var el = document.getElementById(id);
                                    if (el) { el.disabled = false; el.removeAttribute('title'); }
                                });
                                document.getElementById('surat_number').focus();
                                b.hidden = true;
                                document.getElementById('suratSaveBtn').hidden = false;
                            });
                        })();
                    </script>
                @endif

                {{-- Barcode — TERPISAH dari form Nomor/Tanggal di atas, selalu
                     aktif (tidak ikut kunci/Edit): upload TERSIMPAN OTOMATIS
                     begitu file dipilih (AJAX, tanpa reload), dan bisa dihapus
                     kapan saja. --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barcode</label>
                    <div id="assignmentLetterBarcodeContainer">
                        @include('proposals._assignment_letter_barcode')
                    </div>
                </div>
                <script>
                    (function () {
                        var container = document.getElementById('assignmentLetterBarcodeContainer');
                        if (!container) return;
                        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                        function showError(msg) {
                            var err = container.querySelector('.assignment-letter-barcode-error');
                            if (!err) return;
                            err.textContent = msg;
                            err.hidden = false;
                        }

                        function handleResponse(res) {
                            if (res.ok) {
                                return res.text().then(function (html) { container.innerHTML = html; });
                            }
                            return res.json().then(function (data) {
                                var msg = (data.errors && data.errors.assignment_letter_barcode)
                                    ? data.errors.assignment_letter_barcode[0]
                                    : (data.message || 'Gagal memproses barcode.');
                                showError(msg);
                            }).catch(function () {
                                showError('Gagal memproses barcode.');
                            });
                        }

                        container.addEventListener('change', function (e) {
                            if (e.target.id !== 'assignmentLetterBarcodeInput' || !e.target.files.length) return;
                            var fd = new FormData();
                            fd.append('assignment_letter_barcode', e.target.files[0]);
                            fetch('{{ route('projects.assignmentLetterBarcode.store', $project) }}', {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                                body: fd,
                            }).then(handleResponse).catch(function () { showError('Gagal mengunggah barcode.'); });
                        });

                        container.addEventListener('submit', function (e) {
                            if (!e.target.classList.contains('assignment-letter-barcode-delete-form')) return;
                            e.preventDefault();
                            fetch(e.target.action, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: new FormData(e.target),
                            }).then(handleResponse).catch(function () { showError('Gagal menghapus barcode.'); });
                        });
                    })();
                </script>
            @else
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nomor Surat Tugas</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->assignment_letter_number ?: '(belum diisi)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal Surat Tugas</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->assignment_letter_date?->translatedFormat('d F Y') ?: '(belum diisi)' }}</dd>
                    </div>
                </dl>
                @if ($project->assignment_letter_barcode)
                    <div class="mt-2">
                        <dt class="text-gray-500 dark:text-gray-400 text-sm">Barcode</dt>
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($project->assignment_letter_barcode) }}" alt="Barcode"
                             class="mt-1 h-16 w-16 border border-gray-200 rounded object-contain dark:border-gray-600">
                    </div>
                @endif
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Hanya Administrator &amp; General Admin yang dapat mengisi.</p>
            @endcan

            <div id="assignmentStaffContainer" class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                @include('proposals._assignment_staff')
            </div>
            <script>
                (function () {
                    // Tambah/Hapus petugas TANPA reload halaman — kalau ada 3 orang
                    // yang harus ditambah satu-satu, reload penuh tiap kali kerasa
                    // lambat & bolak-balik. Delegasi event dari container supaya
                    // tetap jalan walau kontennya diganti (form baru dari respons).
                    var container = document.getElementById('assignmentStaffContainer');
                    if (!container) return;

                    function submitAjax(form) {
                        fetch(form.action, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: new FormData(form),
                        }).then(function (res) {
                            if (!res.ok) { window.location.reload(); return; }
                            return res.text();
                        }).then(function (html) {
                            if (html !== undefined) container.innerHTML = html;
                        }).catch(function () {
                            window.location.reload();
                        });
                    }

                    container.addEventListener('submit', function (e) {
                        var form = e.target;
                        if (form.id === 'assignmentStaffAddForm' || form.classList.contains('assignment-staff-remove-form')) {
                            e.preventDefault();
                            submitAjax(form);
                        }
                    });
                })();
            </script>

            {{-- ---------- CETAK PDF ---------- --}}
            @can('survey.view')
                <div class="mt-4">
                    @if ($project->assigned_appraiser && $project->survey_date)
                        <a href="{{ route('projects.exportSuratTugas', $project) }}"
                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-gray-800 text-white hover:bg-gray-900">
                            📄 Cetak PDF Surat Tugas
                        </a>
                    @else
                        <p class="text-xs text-gray-400 dark:text-gray-500">Cetak tersedia setelah data penilai lapangan &amp; tanggal survei diisi.</p>
                    @endif
                </div>
            @endcan
        </div>
    @endcanany

    {{-- ===================== CARD DAFTAR TAGIHAN & PEMBAYARAN =====================
         Model tagihan FLEKSIBEL: proyek boleh punya berapa pun invoice/termin.
         Sistem tidak peduli ini termin ke berapa — cukup jumlahkan yang sudah
         Paid vs total_fee utk tahu sisa tagihan (ditampilkan di sini, satu
         tempat, lepas dari status proyek). --}}
    @unless ($project->isCancelled())
        <div id="section-tagihan" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Daftar Tagihan &amp; Pembayaran</h2>
                @if ($project->remaining_balance > 0)
                    <span class="text-xs font-semibold text-amber-600 whitespace-nowrap dark:text-amber-400">
                        Sisa Tagihan: Rp {{ number_format($project->remaining_balance, 0, ',', '.') }}
                    </span>
                @else
                    <span class="text-xs font-semibold text-emerald-600 whitespace-nowrap dark:text-emerald-400">✔ Lunas</span>
                @endif
            </div>

            @if ($project->invoices->isNotEmpty())
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($project->invoices->sortBy('id') as $inv)
                        <div class="py-3 flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p class="font-medium text-gray-900 text-sm dark:text-gray-100">
                                    {{ $inv->invoice_number }}
                                    <span class="text-gray-400 font-normal dark:text-gray-500">— {{ $inv->term_description ?? $inv->invoice_type }}</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Rp {{ number_format($inv->amount, 0, ',', '.') }} —
                                    <span class="{{ $inv->status === 'Paid' ? 'text-green-600 font-semibold' : 'text-orange-500' }}">
                                        {{ $inv->status === 'Paid' ? 'Lunas' : 'Belum Dibayar' }}
                                    </span>
                                    @if ($inv->status === 'Paid' && $inv->kwitansi_number)
                                        &middot; Kwitansi {{ $inv->kwitansi_number }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5">
                                @can('invoices.view')
                                    <a href="{{ route('invoices.exportInvoice', $inv) }}" title="Cetak Invoice"
                                       class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                        </svg>
                                    </a>
                                    @if ($inv->status === 'Paid')
                                        <a href="{{ route('invoices.exportKwitansi', $inv) }}" title="Cetak Kwitansi"
                                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-teal-100 hover:text-teal-700 dark:text-gray-400 dark:hover:bg-teal-900/30 dark:hover:text-teal-300">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                        </a>
                                    @endif
                                @endcan
                                @can('invoices.manage')
                                    {{-- Edit — mitigasi human error (salah nominal/keterangan), boleh
                                         dipakai walau sudah Lunas (lihat catatan di InvoiceController@update). --}}
                                    <button type="button"
                                            onclick="openEditInvoiceModal('{{ route('invoices.update', $inv) }}', {{ (float) $inv->amount }}, {{ \Illuminate\Support\Js::from($inv->term_description ?? '') }}, {{ $inv->status === 'Paid' ? 'true' : 'false' }})"
                                            title="Edit Invoice"
                                            class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-blue-100 hover:text-blue-700 dark:text-gray-400 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                        @include('partials.icon-pencil')
                                    </button>
                                    @if ($inv->status !== 'Paid')
                                        <button type="button" onclick="openVerifyPaidModal('{{ route('invoices.markAsPaid', $inv) }}')" title="Verifikasi Lunas"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-emerald-100 hover:text-emerald-700 dark:text-gray-400 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-300">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </button>
                                    @endif
                                    {{-- Hapus — sekarang tersedia juga untuk invoice yang sudah Lunas
                                         (mitigasi human error: salah verifikasi lunas). Konfirmasi beda
                                         teks kalau sudah Lunas supaya tidak terklik tanpa sadar. --}}
                                    <form action="{{ route('invoices.destroy', $inv) }}" method="POST"
                                          onsubmit="return confirm({{ $inv->status === 'Paid'
                                                ? \Illuminate\Support\Js::from("Invoice {$inv->invoice_number} sudah berstatus Lunas dengan kwitansi {$inv->kwitansi_number}. Yakin ingin menghapusnya? Aksi ini tidak bisa dibatalkan.")
                                                : \Illuminate\Support\Js::from("Batalkan invoice {$inv->invoice_number}?") }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Invoice"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">Belum ada invoice diterbitkan.</p>
            @endif

            @can('invoices.manage')
                @if ($project->remaining_balance > 0)
                    <div class="pt-1">
                        <button type="button" onclick="openInvoiceModal()"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            + Buat Invoice
                        </button>
                    </div>
                @endif
            @endcan
        </div>
    @endunless

    {{-- ===================== CARD FAKTUR PAJAK =====================
         Dipindah ke bawah kartu Daftar Tagihan & Pembayaran (baru relevan
         setelah ada tagihan). Tombol Simpan-nya SATU, dipindah ke kartu
         "Aksi Tersedia" di bawah (terhubung lewat atribut form="fakturForm"
         pada tombolnya) — di sini cuma sisa field + tombol Edit/kunci. --}}
    @canany(['tax_invoice.manage', 'invoices.view'])
        <div id="section-faktur" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
            @php $hasFaktur = $project->tax_invoice_number || $project->tax_invoice_date; @endphp

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Faktur Pajak</h2>
                @can('tax_invoice.manage')
                    <div class="flex items-center gap-1">
                        @if ($hasFaktur)
                            <button type="button" id="fakturEditBtn" title="Edit"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                @include('partials.icon-pencil')
                            </button>
                            <button type="submit" form="fakturForm" id="fakturSaveBtn" hidden title="Simpan"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                @include('partials.icon-check')
                            </button>
                        @else
                            <button type="submit" form="fakturForm" title="Simpan"
                                    class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                @include('partials.icon-check')
                            </button>
                        @endif
                    </div>
                @endcan
            </div>

            @can('tax_invoice.manage')
                <form action="{{ route('proposals.taxInvoice', $project) }}" method="POST" id="fakturForm">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Faktur Pajak</label>
                            <input type="text" name="tax_invoice_number" id="faktur_number"
                                   value="{{ old('tax_invoice_number', $project->tax_invoice_number) }}"
                                   placeholder="Contoh: 010.000-26.00000001"
                                   @disabled($hasFaktur)
                                   @if ($hasFaktur) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Faktur Pajak</label>
                            <input type="date" name="tax_invoice_date" id="faktur_date" lang="id"
                                   value="{{ old('tax_invoice_date', $project->tax_invoice_date?->toDateString()) }}"
                                   @disabled($hasFaktur)
                                   @if ($hasFaktur) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                        </div>
                    </div>
                </form>
                @if ($hasFaktur)
                    <script>
                        (function () {
                            var b = document.getElementById('fakturEditBtn');
                            if (!b) return;
                            b.addEventListener('click', function () {
                                ['faktur_number', 'faktur_date'].forEach(function (id) {
                                    document.getElementById(id).disabled = false;
                                    document.getElementById(id).removeAttribute('title');
                                });
                                document.getElementById('faktur_number').focus();
                                b.hidden = true;
                                document.getElementById('fakturSaveBtn').hidden = false;
                            });
                        })();
                    </script>
                @endif
            @else
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nomor Faktur Pajak</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->tax_invoice_number ?: '(belum diisi)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal Faktur Pajak</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $project->tax_invoice_date?->translatedFormat('d F Y') ?: '(belum diisi)' }}</dd>
                    </div>
                </dl>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Hanya Administrator &amp; General Admin yang dapat mengisi.</p>
            @endcan
        </div>
    @endcanany

    {{-- ===================== CARD NOMOR LAPORAN RESMI =====================
         Dipisah dari kartu "Aksi Tersedia" (2026-09-13, feedback user: field
         ini butuh kartu sendiri yang lebar penuh, bukan numpang sempit di
         Aksi Tersedia). Muncul dari status Pelunasan sampai Selesai.

         Boleh diisi MULAI status Pelunasan, tidak perlu menunggu lunas
         penuh (2026-09-14, feedback user: kadang nomor laporan sudah bisa
         diambil sebelum tagihan lunas — status Pelunasan sendiri sudah
         menjamin draf/pekerjaan disetujui, lihat markDraftComplete()).
         Badge pembayaran di header MURNI informasi, tidak lagi mengunci
         form. Pola kunci/edit form-nya sama seperti Faktur Pajak: sekali
         terisi, field terkunci sampai ikon Edit ditekan.

         Izin KHUSUS final_report.manage/.view (2026-09-14, feedback user:
         hanya Admin Produksi/Admin Keuangan/Administrator yang boleh
         isi — BUKAN Surveyor, beda dari data survei lapangan yang
         dipakai kartu Penilai Lapangan). --}}
    @canany(['final_report.manage', 'final_report.view'])
        @if (in_array($project->status, [\App\Models\Project::STATUS_PELUNASAN, \App\Models\Project::STATUS_SELESAI]))
            <div id="section-laporan-resmi" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
                @php $hasFinalReport = (bool) $project->final_report_number; @endphp
                <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Nomor Laporan Resmi</h2>
                    <div class="flex items-center gap-2">
                        {{-- Badge "Menunggu lunas penuh" dibuang (2026-09-14,
                             feedback user): Nomor Laporan Resmi tetap bisa
                             diisi sebelum lunas, jadi badge itu menyesatkan
                             (kesannya masih terkunci). Badge "sudah lunas"
                             dipertahankan — itu murni info status proyek,
                             tidak menyiratkan sedang terkunci. --}}
                        @if ($project->status === \App\Models\Project::STATUS_SELESAI)
                            <span class="text-xs font-semibold text-emerald-600 whitespace-nowrap dark:text-emerald-400">✔ Seluruh tagihan lunas. Proyek selesai.</span>
                        @endif
                        @can('final_report.manage')
                            <div class="flex items-center gap-1">
                                @if ($hasFinalReport)
                                    <button type="button" id="finalReportEditBtn" title="Edit"
                                            class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                        @include('partials.icon-pencil')
                                    </button>
                                    <button type="submit" form="finalReportForm" id="finalReportSaveBtn" hidden title="Simpan"
                                            class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                        @include('partials.icon-check')
                                    </button>
                                @else
                                    <button type="submit" form="finalReportForm" title="Simpan"
                                            class="grid h-7 w-7 place-items-center rounded-md text-gray-400 hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                        @include('partials.icon-check')
                                    </button>
                                @endif
                            </div>
                        @endcan
                    </div>
                </div>

                @can('final_report.manage')
                    <form action="{{ route('projects.inputFinalReportNumber', $project) }}" method="POST" id="finalReportForm" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">Nomor Laporan Resmi</label>
                                <input type="text" name="final_report_number" id="final_report_number" required @disabled($hasFinalReport)
                                       @if ($hasFinalReport) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                       value="{{ old('final_report_number', $project->final_report_number) }}"
                                       placeholder="00000/2.0131-00/KJPPSPR-PRO/APP/_/____"
                                       class="w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">Tanggal Final</label>
                                <input type="date" name="final_report_date" id="final_report_date" lang="id" @disabled($hasFinalReport)
                                       @if ($hasFinalReport) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                       value="{{ old('final_report_date', $project->final_report_date?->toDateString()) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">Keterangan (opsional)</label>
                            <textarea name="final_report_notes" id="final_report_notes" rows="2" @disabled($hasFinalReport)
                                      @if ($hasFinalReport) title="Terkunci — klik ikon Edit untuk mengubah." @endif
                                      class="w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:border-gray-600">{{ old('final_report_notes', $project->final_report_notes) }}</textarea>
                        </div>
                    </form>
                    @if ($hasFinalReport)
                        <script>
                            (function () {
                                var b = document.getElementById('finalReportEditBtn');
                                if (!b) return;
                                b.addEventListener('click', function () {
                                    ['final_report_number', 'final_report_date', 'final_report_notes'].forEach(function (id) {
                                        document.getElementById(id).disabled = false;
                                        document.getElementById(id).removeAttribute('title');
                                    });
                                    document.getElementById('final_report_number').focus();
                                    b.hidden = true;
                                    document.getElementById('finalReportSaveBtn').hidden = false;
                                });
                            })();
                        </script>
                    @endif
                @elsecan('final_report.view')
                    <div class="text-sm text-gray-500 dark:text-gray-400 space-y-0.5">
                        <p>Nomor Laporan Resmi: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $project->final_report_number ?? '(belum diisi)' }}</span></p>
                        <p>Tanggal Final: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $project->final_report_date?->translatedFormat('d F Y') ?? '(belum diisi)' }}</span></p>
                        @if ($project->final_report_notes)
                            <p>Keterangan: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $project->final_report_notes }}</span></p>
                        @endif
                    </div>
                @endcan
            </div>
        @endif
    @endcanany

    {{-- ===================== PANEL AKSI (GATEKEEPING PER STATUS) =====================
         Diletakkan paling bawah halaman (2026-09-13, feedback user).
         Tombol "Batalkan Project"/"Aktifkan Kembali" sudah pindah jadi
         ikon di header atas (2026-09-14, feedback user) — jadi kartu ini
         cuma relevan untuk status yang masih punya tombol/aksi kontekstual
         (Draft s.d. Pelunasan). Selesai & Batal tidak render kartu ini
         sama sekali, tidak ada gunanya kartu kosong tanpa isi. --}}
    @if (!$project->isCancelled() && $project->status !== \App\Models\Project::STATUS_SELESAI)
    <div id="section-aksi" class="scroll-mt-24 bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2.5 dark:text-gray-400">Catatan &amp; Langkah Berikutnya</h2>

        {{-- ---------- STATUS: DRAFT / MENUNGGU PERSETUJUAN ---------- --}}
        @if (in_array($project->status, [\App\Models\Project::STATUS_DRAFT, \App\Models\Project::STATUS_WAITING_APPROVAL]))
            <p class="text-xs text-gray-400 dark:text-gray-500">
                @if ($project->isPaymentDeferred())
                    Skema Bayar Nanti — tekan "Mulai Pekerjaan (Tanpa DP)" untuk langsung mulai kerja lapangan tanpa invoice, atau terbitkan invoice lewat kartu "Daftar Tagihan &amp; Pembayaran" di atas kalau ingin menagih lebih dulu.
                @else
                    Terbitkan invoice pertama lewat kartu "Daftar Tagihan &amp; Pembayaran" di atas untuk menandai klien setuju.
                @endif
            </p>

        {{-- ---------- STATUS: DP INVOICING ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_DP_INVOICING)
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Menunggu pembayaran pertama diverifikasi (lihat kartu "Daftar Tagihan &amp; Pembayaran" di atas) sebelum penilaian lapangan bisa dimulai.
            </p>

        {{-- ---------- STATUS: IN-PROGRESS / SCHEDULED ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_IN_PROGRESS)
            @can('survey.manage')
                @if (!$project->assigned_appraiser || !$project->survey_date)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Isi Penilai Lapangan &amp; Tanggal Survei lewat kartu "Penilai Lapangan &amp; Tanggal Survei" di atas untuk memulai.
                    </p>
                @endif
            @elsecan('survey.view')
                @if (!$project->assigned_appraiser || !$project->survey_date)
                    <p class="text-sm text-gray-500 dark:text-gray-400">Menunggu Surveyor/Admin Produksi menginput data penilai lapangan.</p>
                @endif
            @endcan

            {{-- Kartu SLA Draft/Final yang detail sudah dipindah jadi badge
                 ringkas di header (di bawah nomor proposal) — tidak diulang
                 lagi di sini supaya halaman tidak terlalu panjang. --}}
            @if ($project->assigned_appraiser && $project->survey_date)
                <div class="space-y-3 mt-1">

                    @if ($project->review_rejected_at && $project->review_status !== \App\Models\Project::REVIEW_APPROVED)
                        <div class="rounded-md border border-rose-200 bg-rose-50 p-2 text-[11px] text-rose-700 dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                            ⚠️ Dikembalikan oleh <strong>{{ $project->reviewRejectedBy->name ?? '-' }}</strong>
                            ({{ $project->review_rejected_at->translatedFormat('d M Y') }}):
                            <span class="italic">&ldquo;{{ $project->review_rejection_note }}&rdquo;</span>
                        </div>
                    @endif

                    {{-- Semua TOMBOL alur review sudah pindah ke floating
                         action bar (partial _floating_actions.blade.php).
                         Yang tersisa di sini murni informasi. --}}
                    @if ($project->review_status === \App\Models\Project::REVIEW_APPROVED)
                        <p class="text-xs text-emerald-700 font-medium dark:text-emerald-400">
                            ✅ Disetujui oleh {{ $project->reviewApprovedBy->name ?? '-' }} ({{ $project->review_approved_at?->translatedFormat('d M Y') }})
                        </p>
                    @endif
                </div>
            @endif

        {{-- ---------- STATUS: PELUNASAN ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_PELUNASAN)
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Draf laporan sudah selesai. Terbitkan &amp; verifikasi sisa tagihan lewat kartu
                "Daftar Tagihan &amp; Pembayaran" di atas — proyek otomatis pindah ke Selesai begitu lunas penuh.
                Nomor Laporan Resmi bisa diisi di kartu "Nomor Laporan Resmi" di atas begitu lunas penuh.
            </p>
        @endif
    </div>
    @endif

</div>

{{-- Sengaja DI LUAR container ".max-w-4xl" — container itu punya transform
     (identity) yang membuatnya jadi containing block untuk position:fixed,
     sehingga bar melayangnya ikut ter-offset ke tengah dokumen, bukan
     tengah layar. --}}
@include('proposals._floating_actions')

{{-- ===================== MODAL 1: BUAT INVOICE (PERSENTASE / NOMINAL SALING SINKRON) ===================== --}}
<div id="invoiceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Buat Invoice</h2>
            <button type="button" onclick="closeInvoiceModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>

        <form action="{{ route('invoices.store', $project) }}" method="POST" class="space-y-3">
            @csrf
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Sisa Tagihan saat ini: <span class="font-semibold text-amber-600 dark:text-amber-400">Rp {{ number_format($project->remaining_balance, 0, ',', '.') }}</span>
                dari total kontrak Rp {{ number_format((float) $project->total_fee, 0, ',', '.') }}.
            </p>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Persentase (%)</label>
                <input type="number" id="inv_percentage" name="percentage" min="0.01" max="100" step="0.01" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Angka Nominal (Rp)</label>
                <input type="number" id="inv_amount" name="amount" min="1" step="1" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (opsional)</label>
                <input type="text" name="term_description" placeholder="Termin 1 / DP 50% / dst."
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Terbit Invoice</label>
                <input type="date" name="invoice_date" value="{{ now()->toDateString() }}" required lang="id"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeInvoiceModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Terbitkan Invoice</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL 1b: EDIT INVOICE (mitigasi human error) =====================
     Ubah nominal/keterangan invoice yang sudah terlanjur dibuat — termasuk
     yang sudah Lunas (mis. salah input nominal, atau salah klik Verifikasi
     Lunas). Lihat InvoiceController@update. --}}
<div id="editInvoiceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Edit Invoice</h2>
            <button type="button" onclick="closeEditInvoiceModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>
        <p id="editInvoiceWarning" hidden class="mb-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-2 dark:text-amber-400 dark:bg-amber-900/20 dark:border-amber-800">
            ⚠️ Invoice ini sudah berstatus Lunas &amp; sudah punya nomor kwitansi. Mengubah nominal/keterangan di sini <strong>tidak</strong> otomatis mengubah PDF Invoice/Kwitansi yang mungkin sudah dicetak/dikirim ke klien — cetak ulang kalau perlu.
        </p>
        <form id="editInvoiceForm" method="POST" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Angka Nominal (Rp)</label>
                <input type="number" id="edit_inv_amount" name="amount" min="1" step="1" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (opsional)</label>
                <input type="text" id="edit_inv_description" name="term_description" placeholder="Termin 1 / DP 50% / dst."
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditInvoiceModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL 2: VERIFIKASI LUNAS (TANGGAL MANUAL) ===================== --}}
<div id="verifyPaidModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Verifikasi Pembayaran</h2>
            <button type="button" onclick="closeVerifyPaidModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>
        <form id="verifyPaidForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pembayaran Diterima</label>
                <input type="date" name="payment_date" id="verify_payment_date" value="{{ now()->toDateString() }}" required lang="id"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600">
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Tanggal ini akan muncul di PDF Kwitansi sebagai tanggal penerimaan.</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeVerifyPaidModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">Konfirmasi Lunas</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL 4: KEMBALIKAN REVIEW (ALASAN WAJIB) ===================== --}}
<div id="reviewRejectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h2 id="reviewRejectTitle" class="text-lg font-semibold">Kembalikan</h2>
            <button type="button" onclick="closeReviewRejectModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-200">&times;</button>
        </div>
        <form id="reviewRejectForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan / Catatan Revisi</label>
                <textarea id="reviewRejectReason" name="reason" rows="3" required
                          placeholder="Jelaskan apa yang perlu diperbaiki..."
                          class="mt-1 w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeReviewRejectModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-md bg-rose-600 text-white hover:bg-rose-700">Kembalikan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const INVOICE_MODAL_TOTAL_FEE = {{ (float) $project->total_fee }};
    const INVOICE_MODAL_REMAINING = {{ (float) $project->remaining_balance }};

    function openInvoiceModal() {
        const amountInput = document.getElementById('inv_amount');
        const percentageInput = document.getElementById('inv_percentage');
        amountInput.value = Math.round(INVOICE_MODAL_REMAINING);
        percentageInput.value = INVOICE_MODAL_TOTAL_FEE > 0
            ? (INVOICE_MODAL_REMAINING / INVOICE_MODAL_TOTAL_FEE * 100).toFixed(2)
            : '';
        document.getElementById('invoiceModal').classList.remove('hidden');
        document.getElementById('invoiceModal').classList.add('flex');
    }
    function closeInvoiceModal() {
        document.getElementById('invoiceModal').classList.add('hidden');
        document.getElementById('invoiceModal').classList.remove('flex');
    }
    (function () {
        const amountInput = document.getElementById('inv_amount');
        const percentageInput = document.getElementById('inv_percentage');
        if (!amountInput || !percentageInput) return;

        amountInput.addEventListener('input', function () {
            if (INVOICE_MODAL_TOTAL_FEE <= 0) return;
            const amount = parseFloat(amountInput.value);
            if (isNaN(amount)) return;
            percentageInput.value = (amount / INVOICE_MODAL_TOTAL_FEE * 100).toFixed(2);
        });
        percentageInput.addEventListener('input', function () {
            const pct = parseFloat(percentageInput.value);
            if (isNaN(pct)) return;
            amountInput.value = Math.round(INVOICE_MODAL_TOTAL_FEE * pct / 100);
        });
    })();

    function openEditInvoiceModal(actionUrl, amount, description, isPaid) {
        document.getElementById('editInvoiceForm').action = actionUrl;
        document.getElementById('edit_inv_amount').value = Math.round(amount);
        document.getElementById('edit_inv_description').value = description || '';
        document.getElementById('editInvoiceWarning').hidden = !isPaid;
        document.getElementById('editInvoiceModal').classList.remove('hidden');
        document.getElementById('editInvoiceModal').classList.add('flex');
    }
    function closeEditInvoiceModal() {
        document.getElementById('editInvoiceModal').classList.add('hidden');
        document.getElementById('editInvoiceModal').classList.remove('flex');
    }

    function openVerifyPaidModal(actionUrl) {
        document.getElementById('verifyPaidForm').action = actionUrl;
        document.getElementById('verify_payment_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('verifyPaidModal').classList.remove('hidden');
        document.getElementById('verifyPaidModal').classList.add('flex');
    }
    function closeVerifyPaidModal() {
        document.getElementById('verifyPaidModal').classList.add('hidden');
        document.getElementById('verifyPaidModal').classList.remove('flex');
    }


    // ===================== MODAL: KEMBALIKAN REVIEW (perlu alasan) =====================
    function openReviewRejectModal(actionUrl, title) {
        document.getElementById('reviewRejectForm').action = actionUrl;
        document.getElementById('reviewRejectTitle').textContent = title;
        document.getElementById('reviewRejectReason').value = '';
        document.getElementById('reviewRejectModal').classList.remove('hidden');
        document.getElementById('reviewRejectModal').classList.add('flex');
    }
    function closeReviewRejectModal() {
        document.getElementById('reviewRejectModal').classList.add('hidden');
        document.getElementById('reviewRejectModal').classList.remove('flex');
    }
</script>
@endsection