@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $project->proposal_number }}</h1>
            <p class="text-sm text-gray-500">
                Tanggal proposal: <span class="font-medium text-gray-700">{{ $project->effective_proposal_date->translatedFormat('d F Y') }}</span>
                <span class="text-gray-400">&middot; input {{ $project->created_at->translatedFormat('d F Y') }}</span>
            </p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold {{ $project->status_badge_classes }}">
                {{ $project->status }}
            </span>
            @if ($project->isCancelled())
                <span class="text-xs text-rose-600">
                    Dibatalkan {{ optional($project->cancelled_at)->translatedFormat('d F Y') ?? '—' }}
                    @if ($project->status_before_cancel)
                        · sebelumnya: {{ $project->status_before_cancel }}
                    @endif
                </span>
            @endif
            @can('proposals.manage')
                <a href="{{ route('proposals.texts', $project) }}"
                   class="text-xs font-medium text-blue-600 hover:text-blue-800 whitespace-nowrap">
                    ✏️ Editor Teks Proposal
                    @if (($project->section_texts_count ?? 0) > 0)
                        <span class="ml-1 px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300">
                            {{ $project->section_texts_count }} bab diedit
                        </span>
                    @endif
                </a>

                {{-- Batalkan Project — non-destruktif (status jadi Batal, bisa diaktifkan kembali). --}}
                @unless ($project->isCancelled())
                    <form action="{{ route('proposals.cancel', $project) }}" method="POST"
                          onsubmit="return confirm('Batalkan proyek {{ $project->proposal_number }}? Data TIDAK dihapus — status menjadi Batal dan bisa diaktifkan kembali kapan saja.')">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-800 whitespace-nowrap">
                            ⛔ Batalkan Project
                        </button>
                    </form>
                @endunless
            @endcan
        </div>
    </div>

    {{-- ===================== CARD INFORMASI UTAMA ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Informasi Proyek</h2>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div class="col-span-2">
                <dt class="text-gray-500">Pemberi Tugas</dt>
                <dd class="font-medium text-gray-900">
                    {{ $project->instructingClient->client_name }}
                    <span class="text-gray-400 font-normal">({{ $project->instructingClient->client_type }})</span>
                </dd>
                <dd class="text-gray-600 text-xs mt-0.5 whitespace-pre-line">{{ $project->instructingClient->address ?: '(alamat belum diisi pada data klien)' }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-gray-500">Pengguna Laporan</dt>
                <dd class="font-medium text-gray-900 mt-1 space-y-2">
                    @foreach ($project->intendedUsers as $user)
                        <div class="text-xs bg-gray-50 border border-gray-200 rounded-md p-2">
                            <div class="font-semibold text-gray-700">{{ $loop->iteration }}. {{ $user->client_name }}
                                <span class="text-gray-400 font-normal">({{ $user->client_type }})</span>
                            </div>
                            <div class="text-gray-500 whitespace-pre-line">{{ $user->address ?: '(alamat belum diisi pada data klien)' }}</div>
                        </div>
                    @endforeach
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Jenis Aset</dt>
                <dd class="font-medium text-gray-900">{{ $project->asset_type }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-gray-500">Lokasi Aset</dt>
                <dd class="font-medium text-gray-900">{{ $project->asset_address }}</dd>
            </div>

            {{-- ===================== RINCIAN OBJEK PENILAIAN ===================== --}}
            @if ($project->valuationObjects && $project->valuationObjects->isNotEmpty())
                <div class="col-span-2">
                    <dt class="text-gray-500">Objek Penilaian ({{ $project->valuationObjects->count() }})</dt>
                    <dd class="font-medium text-gray-900 mt-1 space-y-2">
                        @foreach ($project->valuationObjects as $object)
                            <div class="text-xs bg-gray-50 border border-gray-200 rounded-md p-2">
                                <div class="font-semibold text-gray-700">{{ $loop->iteration }}. {{ $object->short_label }}</div>
                                <div class="text-gray-500">{{ $object->location }}</div>
                                <div class="text-gray-500">Hak: {{ $object->ownership_form }} – a.n. {{ $object->owner_name }}</div>
                            </div>
                        @endforeach
                    </dd>
                </div>
            @endif

            <div class="col-span-2">
                <dt class="text-gray-500">Jenis Laporan</dt>
                <dd class="font-medium text-gray-900">{{ $project->report_style_label }}</dd>
                <dd class="text-gray-500 text-xs mt-0.5">
                    SLA Laporan Draft/Resume:
                    <span class="font-medium text-gray-700">{{ $project->sla_draft_days ? $project->sla_draft_days . ' hari kerja' : '—' }}</span>
                    &nbsp;·&nbsp;
                    SLA Laporan Final:
                    <span class="font-medium text-gray-700">{{ $project->sla_final_days ? $project->sla_final_days . ' hari kerja' : '—' }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Biaya Jasa</dt>
                <dd class="font-medium text-gray-900">Rp {{ number_format($project->total_fee, 0, ',', '.') }}</dd>
                <dd class="text-gray-500 text-xs mt-0.5">
                    Fee Rp {{ number_format($project->fee_professional, 0, ',', '.') }}
                    + PPN Rp {{ number_format($project->fee_ppn_amount, 0, ',', '.') }}
                    @if (($project->transport_cost ?? 0) > 0)
                        + Transport Rp {{ number_format($project->fee_transport_display, 0, ',', '.') }}
                    @endif
                    · {{ $project->fee_ppn_included ? 'Fee sudah termasuk PPN' : 'PPN ditambahkan atas Fee' }}
                    @if ($project->fee_breakdown) · ditampilkan sebagai rincian @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Penandatangan Proposal</dt>
                @if ($project->signedBy)
                    <dd class="font-medium text-gray-900">{{ $project->signedBy->name }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5">
                        {{ $project->signedBy->partner_status ?: config('kjpp.signatory.title') }} · biodata dari akun pengguna
                    </dd>
                @else
                    <dd class="font-medium text-gray-900">{{ config('kjpp.signatory.name') }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5">Penandatangan baku kantor (belum dipilih di proposal)</dd>
                @endif
            </div>
            <div>
                <dt class="text-gray-500">Pihak yang Menyetujui</dt>
                <dd class="font-medium text-gray-900">{{ $project->approver_name ?: $project->instructingClient->client_name }}</dd>
                @unless ($project->approver_name)
                    <dd class="text-gray-500 text-xs mt-0.5">Otomatis = nama Pemberi Tugas (belum diisi manual)</dd>
                @endunless
            </div>
            <div>
                <dt class="text-gray-500">Rekening Pembayaran</dt>
                @if ($project->bank)
                    <dd class="font-medium text-gray-900">{{ $project->bank->bank_name }}{{ $project->bank->branch ? ' (' . $project->bank->branch . ')' : '' }} — {{ $project->bank->account_number }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5">a.n. {{ $project->bank->account_name }} · dipilih di proposal</dd>
                @elseif ($default = \App\Models\Bank::default())
                    <dd class="font-medium text-gray-900">{{ $default->bank_name }}{{ $default->branch ? ' (' . $default->branch . ')' : '' }} — {{ $default->account_number }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5">a.n. {{ $default->account_name }} · rekening default kantor</dd>
                @else
                    <dd class="font-medium text-gray-900">{{ config('kjpp.bank_account.bank_name') }} — {{ config('kjpp.bank_account.account_number') }}</dd>
                    <dd class="text-gray-500 text-xs mt-0.5">dari config (belum ada master rekening)</dd>
                @endif
            </div>
        </dl>
    </div>

    {{-- ===================== CARD FAKTUR PAJAK ===================== --}}
    @canany(['tax_invoice.manage', 'invoices.view'])
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Faktur Pajak</h2>

            @php $hasFaktur = $project->tax_invoice_number || $project->tax_invoice_date; @endphp

            @can('tax_invoice.manage')
                <form action="{{ route('proposals.taxInvoice', $project) }}" method="POST" id="fakturForm">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nomor Faktur Pajak</label>
                            <input type="text" name="tax_invoice_number" id="faktur_number"
                                   value="{{ old('tax_invoice_number', $project->tax_invoice_number) }}"
                                   placeholder="Contoh: 010.000-26.00000001"
                                   @disabled($hasFaktur)
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Faktur Pajak</label>
                            <input type="date" name="tax_invoice_date" id="faktur_date"
                                   value="{{ old('tax_invoice_date', $project->tax_invoice_date?->toDateString()) }}"
                                   @disabled($hasFaktur)
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        @if ($hasFaktur)
                            <button type="button" id="fakturEditBtn"
                                    class="px-4 py-1.5 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                                ✏️ Edit
                            </button>
                            <button type="submit" id="fakturSaveBtn" hidden
                                    class="px-4 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Simpan
                            </button>
                            <span id="fakturLockNote" class="text-xs text-gray-400">🔒 Terkunci — klik Edit untuk mengubah.</span>
                        @else
                            <button type="submit"
                                    class="px-4 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Simpan
                            </button>
                        @endif
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
                                });
                                document.getElementById('faktur_number').focus();
                                b.hidden = true;
                                document.getElementById('fakturSaveBtn').hidden = false;
                                document.getElementById('fakturLockNote').hidden = true;
                            });
                        })();
                    </script>
                @endif
            @else
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Nomor Faktur Pajak</dt>
                        <dd class="font-medium text-gray-900">{{ $project->tax_invoice_number ?: '(belum diisi)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tanggal Faktur Pajak</dt>
                        <dd class="font-medium text-gray-900">{{ $project->tax_invoice_date?->translatedFormat('d F Y') ?: '(belum diisi)' }}</dd>
                    </div>
                </dl>
                <p class="mt-2 text-xs text-gray-400">Hanya Administrator &amp; Admin Keuangan yang dapat mengisi.</p>
            @endcan
        </div>
    @endcanany

    {{-- ===================== CARD DAFTAR TAGIHAN & PEMBAYARAN =====================
         Model tagihan FLEKSIBEL: proyek boleh punya berapa pun invoice/termin.
         Sistem tidak peduli ini termin ke berapa — cukup jumlahkan yang sudah
         Paid vs total_fee utk tahu sisa tagihan (ditampilkan di sini, satu
         tempat, lepas dari status proyek). --}}
    @unless ($project->isCancelled())
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Daftar Tagihan &amp; Pembayaran</h2>
                @if ($project->remaining_balance > 0)
                    <span class="text-xs font-semibold text-amber-600 whitespace-nowrap">
                        Sisa Tagihan: Rp {{ number_format($project->remaining_balance, 0, ',', '.') }}
                    </span>
                @else
                    <span class="text-xs font-semibold text-emerald-600 whitespace-nowrap">✔ Lunas</span>
                @endif
            </div>

            @if ($project->invoices->isNotEmpty())
                <div class="divide-y divide-gray-100">
                    @foreach ($project->invoices->sortBy('id') as $inv)
                        <div class="py-3 flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p class="font-medium text-gray-900 text-sm">
                                    {{ $inv->invoice_number }}
                                    <span class="text-gray-400 font-normal">— {{ $inv->term_description ?? $inv->invoice_type }}</span>
                                </p>
                                <p class="text-xs text-gray-500">
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
                                       class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                        </svg>
                                    </a>
                                    @if ($inv->status === 'Paid')
                                        <a href="{{ route('invoices.exportKwitansi', $inv) }}" title="Cetak Kwitansi"
                                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-teal-100 hover:text-teal-700">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                        </a>
                                    @endif
                                @endcan
                                @can('invoices.manage')
                                    @if ($inv->status !== 'Paid')
                                        <button type="button" onclick="openVerifyPaidModal('{{ route('invoices.markAsPaid', $inv) }}')" title="Verifikasi Lunas"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-emerald-100 hover:text-emerald-700">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </button>
                                        <form action="{{ route('invoices.destroy', $inv) }}" method="POST"
                                              onsubmit="return confirm('Batalkan invoice {{ $inv->invoice_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Batalkan Invoice"
                                                    class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">Belum ada invoice diterbitkan.</p>
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

    {{-- ===================== PANEL AKSI (GATEKEEPING PER STATUS) ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2.5">Aksi Tersedia</h2>

        @if ($project->isCancelled())
            <div class="rounded-md border border-rose-200 bg-rose-50 p-3 space-y-2">
                <p class="text-xs text-rose-700 font-medium">⛔ Proyek ini dibatalkan. Data tetap tersimpan; edit &amp; aksi lain dinonaktifkan.</p>
                @can('proposals.manage')
                    <form action="{{ route('proposals.reactivate', $project) }}" method="POST"
                          onsubmit="return confirm('Aktifkan kembali proyek {{ $project->proposal_number }}? Status akan kembali ke: {{ $project->status_before_cancel ?: \App\Models\Project::STATUS_DRAFT }}.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                            ↩️ Aktifkan Kembali
                        </button>
                    </form>
                @endcan
            </div>
        @else

        {{-- ---------- STATUS: DRAFT / MENUNGGU PERSETUJUAN ---------- --}}
        @if (in_array($project->status, [\App\Models\Project::STATUS_DRAFT, \App\Models\Project::STATUS_WAITING_APPROVAL]))
            <div class="flex flex-wrap gap-2">
                @can('proposals.view')
                    <a href="{{ route('proposals.exportPdf', $project) }}"
                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-gray-800 text-white hover:bg-gray-900">
                        📄 Cetak PDF
                    </a>
                    <a href="{{ route('proposals.exportWord', $project) }}"
                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-700 text-white hover:bg-blue-800">
                        📝 Unduh Word
                    </a>
                @endcan

                @can('proposals.manage')
                    @if ($project->status === \App\Models\Project::STATUS_DRAFT)
                        <a href="{{ route('proposals.edit', $project) }}"
                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                            ✏️ Edit
                        </a>
                    @endif
                @endcan
            </div>
            <p class="mt-2 text-xs text-gray-400">
                Terbitkan invoice pertama lewat kartu "Daftar Tagihan &amp; Pembayaran" di atas untuk menandai klien setuju.
            </p>

        {{-- ---------- STATUS: DP INVOICING ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_DP_INVOICING)
            <p class="text-xs text-gray-500">
                Menunggu pembayaran pertama diverifikasi (lihat kartu "Daftar Tagihan &amp; Pembayaran" di atas) sebelum penilaian lapangan bisa dimulai.
            </p>

        {{-- ---------- STATUS: IN-PROGRESS / SCHEDULED ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_IN_PROGRESS)
            @can('survey.manage')
                @if (!$project->assigned_appraiser || !$project->survey_date)
                    {{-- Form input penilai lapangan & tanggal survei --}}
                    <form action="{{ route('projects.inputSurveyData', $project) }}" method="POST" class="space-y-3 max-w-md">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Penilai Lapangan</label>
                            <select name="assigned_appraiser_id" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">-- Pilih Penilai --</option>
                                @foreach ($activeUsers as $appraiserOption)
                                    <option value="{{ $appraiserOption->id }}">
                                        {{ $appraiserOption->name }} ({{ $appraiserOption->role->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400">
                                Daftar ini menampilkan semua pengguna aktif – pilih akun Surveyor/Admin Produksi yang benar-benar turun lapangan.
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Survei</label>
                            <input type="date" name="survey_date" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            Simpan Jadwal Survei
                        </button>
                    </form>
                @endif
            @elsecan('survey.view')
                @if (!$project->assigned_appraiser || !$project->survey_date)
                    <p class="text-sm text-gray-500">Menunggu Surveyor/Admin Produksi menginput data penilai lapangan.</p>
                @endif
            @endcan

            @if ($project->assigned_appraiser && $project->survey_date)
                @php
                    // Label & warna kartu SLA Final SEBELUM disetujui (belum ada
                    // tanggal utk dihitung mundur) — supaya kartu ini selalu
                    // kelihatan, bukan cuma muncul setelah tahap approved.
                    $reviewWaiting = [
                        null                                       => ['Belum diajukan', 'text-gray-400', 'border-gray-200 bg-gray-50', 'Menunggu Surveyor mengajukan hasil untuk direview.'],
                        \App\Models\Project::REVIEW_SUBMITTED       => ['Menunggu Reviewer', 'text-blue-600', 'border-blue-200 bg-blue-50', 'Diajukan ' . ($project->review_submitted_at?->translatedFormat('d M Y') ?? '-') . ' oleh ' . ($project->reviewSubmittedBy->name ?? '-') . '.'],
                        \App\Models\Project::REVIEW_REVIEWED        => ['Menunggu Admin Produksi', 'text-amber-600', 'border-amber-200 bg-amber-50', 'Direview ' . ($project->reviewed_at?->translatedFormat('d M Y') ?? '-') . ' oleh ' . ($project->reviewedBy->name ?? '-') . '.'],
                    ][$project->review_status] ?? null;
                @endphp
                <div class="space-y-3 mt-1">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500">Penilai Lapangan</dt>
                            <dd class="font-medium text-gray-900">{{ $project->assigned_appraiser }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Tanggal Survei</dt>
                            <dd class="font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($project->survey_date)->translatedFormat('d F Y') }}
                            </dd>
                        </div>
                    </dl>

                    {{-- ---------- 2 KARTU SLA BERDAMPINGAN: DRAFT & FINAL ---------- --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div id="sla-countdown" data-deadline="{{ $project->estimated_completion_date?->toDateString() }}"
                             class="rounded-md border p-2.5">
                            <div class="text-[11px] text-gray-500">SLA Draft/Resume</div>
                            <div id="sla-countdown-text" class="text-sm font-bold">Menghitung...</div>
                            <div class="text-[11px] text-gray-400">Target: {{ $project->estimated_completion_date_formatted }}</div>
                        </div>

                        @if ($project->review_status === \App\Models\Project::REVIEW_APPROVED)
                            <div id="sla-final-countdown" data-deadline="{{ $project->estimated_final_completion_date?->toDateString() }}"
                                 class="rounded-md border p-2.5">
                                <div class="text-[11px] text-gray-500">SLA Laporan Final</div>
                                <div id="sla-final-countdown-text" class="text-sm font-bold">Menghitung...</div>
                                <div class="text-[11px] text-gray-400">Target: {{ $project->estimated_final_completion_date_formatted }}</div>
                            </div>
                        @else
                            <div class="rounded-md border p-2.5 {{ $reviewWaiting[2] }}">
                                <div class="text-[11px] text-gray-500">SLA Laporan Final</div>
                                <div class="text-sm font-bold {{ $reviewWaiting[1] }}">{{ $reviewWaiting[0] }}</div>
                                <div class="text-[11px] text-gray-400">{{ $reviewWaiting[3] }}</div>
                            </div>
                        @endif
                    </div>

                    @if ($project->review_rejected_at && $project->review_status !== \App\Models\Project::REVIEW_APPROVED)
                        <div class="rounded-md border border-rose-200 bg-rose-50 p-2 text-[11px] text-rose-700">
                            ⚠️ Dikembalikan oleh <strong>{{ $project->reviewRejectedBy->name ?? '-' }}</strong>
                            ({{ $project->review_rejected_at->translatedFormat('d M Y') }}):
                            <span class="italic">&ldquo;{{ $project->review_rejection_note }}&rdquo;</span>
                        </div>
                    @endif

                    {{-- ---------- SEMUA TOMBOL AKSI DALAM 1 BARIS ---------- --}}
                    <div class="flex flex-wrap items-center gap-2">
                        @can('survey.view')
                            <a href="{{ route('projects.exportSuratTugas', $project) }}"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-gray-800 text-white hover:bg-gray-900">
                                📄 Cetak PDF Surat Tugas
                            </a>
                        @endcan
                        @can('invoices.manage')
                            <form action="{{ route('projects.markDraftComplete', $project) }}" method="POST"
                                  onsubmit="return confirm('Tandai draf laporan proyek {{ $project->proposal_number }} sudah selesai? Status akan berpindah ke Pelunasan.')">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">
                                    📝 Tandai Draf Selesai
                                </button>
                            </form>
                        @endcan

                        {{-- ---------- ALUR REVIEW SLA FINAL (independen dari invoice pelunasan) ---------- --}}
                        @if (is_null($project->review_status))
                            @can('survey.manage')
                                <form action="{{ route('projects.review.submit', $project) }}" method="POST"
                                      onsubmit="return confirm('Ajukan hasil pekerjaan proyek {{ $project->proposal_number }} untuk direview?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                                        📨 Submit untuk Review
                                    </button>
                                </form>
                            @endcan
                        @elseif ($project->review_status === \App\Models\Project::REVIEW_SUBMITTED && auth()->user()->isReviewer())
                            <form action="{{ route('projects.review.approve', $project) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                                    ✅ Tandai Sudah Direview
                                </button>
                            </form>
                            <button type="button"
                                    onclick="openReviewRejectModal('{{ route('projects.review.rejectToSurveyor', $project) }}', 'Kembalikan ke Surveyor')"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-rose-300 text-rose-600 hover:bg-rose-50">
                                ↩️ Kembalikan ke Surveyor
                            </button>
                        @elseif ($project->review_status === \App\Models\Project::REVIEW_REVIEWED)
                            @can('proposals.manage')
                                <form action="{{ route('projects.review.confirm', $project) }}" method="POST"
                                      onsubmit="return confirm('Konfirmasi pekerjaan proyek {{ $project->proposal_number }} sudah disetujui? SLA Laporan Final akan mulai dihitung.')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                                        ✅ Konfirmasi Disetujui
                                    </button>
                                </form>
                                <button type="button"
                                        onclick="openReviewRejectModal('{{ route('projects.review.rejectToReviewer', $project) }}', 'Kembalikan ke Reviewer')"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-rose-300 text-rose-600 hover:bg-rose-50">
                                    ↩️ Kembalikan ke Reviewer
                                </button>
                            @endcan
                        @elseif ($project->review_status === \App\Models\Project::REVIEW_APPROVED)
                            <span class="text-xs text-emerald-700 font-medium">
                                ✅ Disetujui oleh {{ $project->reviewApprovedBy->name ?? '-' }} ({{ $project->review_approved_at?->translatedFormat('d M Y') }})
                            </span>
                        @endif
                    </div>
                </div>
            @endif

        {{-- ---------- STATUS: PELUNASAN ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_PELUNASAN)
            <div class="space-y-3">
                <p class="text-xs text-gray-500">
                    Draf laporan sudah selesai. Terbitkan &amp; verifikasi sisa tagihan lewat kartu
                    "Daftar Tagihan &amp; Pembayaran" di atas — proyek otomatis pindah ke Selesai begitu lunas penuh.
                </p>

                {{-- Input Nomor Laporan Resmi - TERKUNCI sampai lunas penuh (begitu lunas, status
                     langsung Selesai jadi baris ini otomatis tidak pernah tampil ter-unlock di sini). --}}
                <div class="rounded-md border border-dashed border-gray-300 p-3 bg-gray-50">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nomor Laporan Resmi</label>
                    <div class="flex gap-2">
                        <input type="text" disabled placeholder="Terkunci — tunggu tagihan lunas penuh"
                               class="flex-1 rounded-md border-gray-300 bg-gray-100 text-gray-400 shadow-sm cursor-not-allowed">
                        <button type="button" disabled
                                class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-300 text-gray-500 cursor-not-allowed">
                            🔒 Simpan
                        </button>
                    </div>
                </div>
            </div>

        {{-- ---------- STATUS: SELESAI ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_SELESAI)
            <div class="space-y-3">
                <p class="text-xs text-green-700 font-medium">✔ Seluruh tagihan lunas. Proyek selesai.</p>

                {{-- Input Nomor Laporan Resmi - TERBUKA --}}
                @can('survey.manage')
                    <form action="{{ route('projects.inputFinalReportNumber', $project) }}" method="POST" class="max-w-md">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Laporan Resmi</label>
                        <div class="flex gap-2">
                            <input type="text" name="final_report_number" required
                                   value="{{ $project->final_report_number }}"
                                   placeholder="Contoh: LP/KJPP/2026/001"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm">
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Simpan
                            </button>
                        </div>
                    </form>
                @elsecan('survey.view')
                    <p class="text-sm text-gray-500">Nomor Laporan Resmi: <span class="font-medium text-gray-900">{{ $project->final_report_number ?? '(belum diisi)' }}</span></p>
                @endcan
            </div>
        @endif
        @endif
    </div>
</div>

{{-- ===================== MODAL 1: BUAT INVOICE (PERSENTASE / NOMINAL SALING SINKRON) ===================== --}}
<div id="invoiceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Buat Invoice</h2>
            <button type="button" onclick="closeInvoiceModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>

        <form action="{{ route('invoices.store', $project) }}" method="POST" class="space-y-3">
            @csrf
            <p class="text-xs text-gray-500">
                Sisa Tagihan saat ini: <span class="font-semibold text-amber-600">Rp {{ number_format($project->remaining_balance, 0, ',', '.') }}</span>
                dari total kontrak Rp {{ number_format((float) $project->total_fee, 0, ',', '.') }}.
            </p>
            <div>
                <label class="block text-sm font-medium text-gray-700">Persentase (%)</label>
                <input type="number" id="inv_percentage" name="percentage" min="0.01" max="100" step="0.01" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Angka Nominal (Rp)</label>
                <input type="number" id="inv_amount" name="amount" min="1" step="1" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Keterangan (opsional)</label>
                <input type="text" name="term_description" placeholder="Termin 1 / DP 50% / dst."
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Terbit Invoice</label>
                <input type="date" name="invoice_date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeInvoiceModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Terbitkan Invoice</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL 2: VERIFIKASI LUNAS (TANGGAL MANUAL) ===================== --}}
<div id="verifyPaidModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Verifikasi Pembayaran</h2>
            <button type="button" onclick="closeVerifyPaidModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="verifyPaidForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Pembayaran Diterima</label>
                <input type="date" name="payment_date" id="verify_payment_date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="mt-1 text-xs text-gray-400">Tanggal ini akan muncul di PDF Kwitansi sebagai tanggal penerimaan.</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeVerifyPaidModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">Konfirmasi Lunas</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL 4: KEMBALIKAN REVIEW (ALASAN WAJIB) ===================== --}}
<div id="reviewRejectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 id="reviewRejectTitle" class="text-lg font-semibold">Kembalikan</h2>
            <button type="button" onclick="closeReviewRejectModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="reviewRejectForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Alasan / Catatan Revisi</label>
                <textarea id="reviewRejectReason" name="reason" rows="3" required
                          placeholder="Jelaskan apa yang perlu diperbaiki..."
                          class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeReviewRejectModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Batal</button>
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

    // ===================== SLA COUNTDOWN (client-side, live) =====================
    // Dipakai bareng utk SLA Draft (#sla-countdown) & SLA Final
    // (#sla-final-countdown) — rumus & warna sama, cuma box/deadline beda.
    function initSlaCountdown(boxId, textId) {
        const box = document.getElementById(boxId);
        if (!box) return;

        const deadlineStr = box.dataset.deadline;
        if (!deadlineStr) return;

        const deadline = new Date(deadlineStr + 'T23:59:59');
        const textEl = document.getElementById(textId);

        function render() {
            const now = new Date();
            const diffMs = deadline - now;
            const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

            if (diffDays < 0) {
                textEl.textContent = `⚠ Lewat deadline ${Math.abs(diffDays)} hari`;
                textEl.className = 'text-base font-bold text-red-600';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-red-300 bg-red-50';
            } else if (diffDays === 0) {
                textEl.textContent = '⚠ Deadline hari ini';
                textEl.className = 'text-base font-bold text-orange-600';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-orange-300 bg-orange-50';
            } else if (diffDays <= 2) {
                textEl.textContent = `${diffDays} hari lagi`;
                textEl.className = 'text-base font-bold text-orange-600';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-orange-300 bg-orange-50';
            } else {
                textEl.textContent = `${diffDays} hari lagi`;
                textEl.className = 'text-base font-bold text-green-700';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-green-300 bg-green-50';
            }
        }

        render();
        setInterval(render, 60 * 60 * 1000);
    }
    initSlaCountdown('sla-countdown', 'sla-countdown-text');
    initSlaCountdown('sla-final-countdown', 'sla-final-countdown-text');

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