@extends('layouts.app')

@section('title', 'Data Lengkap ' . $project->proposal_number)

@section('content')
{{-- Halaman baca-saja seluruh data proyek (2026-09-23, feedback user): versi
     "lihat" dari form Edit Proposal, termasuk isian yang tidak tampil di
     kartu Informasi Proyek. --}}
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $tgl = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d F Y') : '—';
    $card = 'rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800';
@endphp

<div class="max-w-7xl mx-auto py-8 space-y-6">
    <x-page-header :title="$project->proposal_number" subtitle="Seluruh data proyek, hanya untuk dibaca.">
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $project->status_badge_classes }}">{{ $project->status }}</span>
        <a href="{{ route('proposals.show', $project) }}"
           class="inline-flex h-[38px] items-center rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">
            Kembali ke proyek
        </a>
        @can('proposals.manage')
            @if (! $project->isDone() || auth()->user()->isAdministrator())
                <a href="{{ route('proposals.edit', $project) }}"
                   class="inline-flex h-[38px] items-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">Edit Proposal</a>
            @endif
        @endcan
    </x-page-header>

    @foreach ([
        'Proposal & Pihak' => [
            'Nomor Proposal'      => $project->proposal_number,
            'Tanggal Proposal'    => $tgl($project->effective_proposal_date),
            'Dasar Permintaan'    => $project->request_basis,
            'Pemberi Tugas'       => optional($project->instructingClient)->client_name,
            'Alamat Pemberi Tugas' => optional($project->instructingClient)->address,
            'Nama Klien (an.)'    => $project->effective_client_name,
            'Pengguna Laporan'    => $project->intendedUsers->map(fn ($u) => $u->client_name)->implode("\n"),
            'Pihak yang Menyetujui' => $project->effective_approver_name,
            'Penanggung Jawab'    => optional($project->signedBy)->name,
            'Marketing'           => $project->marketing_name,
        ],
        'Lingkup & SLA' => [
            'Tujuan Penilaian'    => $project->proposal_purpose,
            'Dasar Nilai'         => $project->value_basis_label,
            'Jenis Laporan'       => $project->report_style_label,
            'SLA Laporan Draft'   => $project->sla_draft_days ? $project->sla_draft_days . ' hari kerja' : null,
            'SLA Laporan Final'   => $project->sla_final_days ? $project->sla_final_days . ' hari kerja' : null,
            'Klasifikasi PSAK'    => $project->psak_classification,
            'Tanggal Laporan Keuangan' => $project->financial_reporting_date ? $tgl($project->financial_reporting_date) : null,
            'Perusahaan Terbuka'  => $project->is_public_company ? 'Ya' : 'Tidak',
        ],
        'Biaya & Pembayaran' => [
            'Biaya Jasa (total)'  => $rp($project->total_fee),
            'Fee Profesional'     => $rp($project->fee_professional),
            'PPN'                 => $rp($project->fee_ppn_amount),
            'Transport'           => ($project->transport_cost ?? 0) > 0 ? $rp($project->fee_transport_display) : null,
            'Status PPN'          => $project->fee_ppn_included ? 'Sudah termasuk PPN' : 'PPN ditambahkan',
            'Transport & Akomodasi' => $project->transport_reimbursed ? 'Ditanggung klien (reimburse)' : 'Termasuk biaya',
            'Tampilan Biaya'      => $project->fee_breakdown ? 'Dirinci' : 'Satu angka',
            'Skema Pembayaran'    => $project->payment_scheme,
            'Termin'              => collect($project->paymentTermPercents())->map(fn ($p) => rtrim(rtrim(number_format($p, 2, ',', '.'), '0'), ',') . '%')->implode(' + '),
            'Rekening'            => $project->bank ? $project->bank->bank_name . ' — ' . $project->bank->account_number : null,
            'Sisa Tagihan'        => $rp($project->uninvoiced_balance),
            'Sisa Pelunasan'      => $rp($project->remaining_balance),
        ],
        'Dokumen & Tanda Tangan' => [
            'Barcode Tanda Tangan' => $project->use_signature_barcode ? ($project->signature_barcode ? 'Dipakai (file tersimpan)' : 'Dipilih, file belum diunggah') : 'Tidak dipakai',
            'Stempel Kantor'       => $project->use_stamp ? 'Dipakai' : 'Tidak dipakai',
            'Surat Representasi'   => $project->representative_limited ? 'Inspeksi terbatas' : 'Umum',
            'Nomor Surat Tugas'    => $project->assignment_letter_number,
            'Tanggal Surat Tugas'  => $project->assignment_letter_date ? $tgl($project->assignment_letter_date) : null,
            'Nomor Faktur Pajak'   => $project->tax_invoice_number,
            'Tanggal Faktur Pajak' => $project->tax_invoice_date ? $tgl($project->tax_invoice_date) : null,
            'Nomor Laporan Final'  => $project->final_report_number,
            'Tanggal Laporan Final' => $project->final_report_date ? $tgl($project->final_report_date) : null,
            'Catatan Laporan Final' => $project->final_report_notes,
        ],
        'Pelaksanaan' => [
            'Tahap Sekarang'      => $project->stage['label'] . ($project->stage['actor'] ? ' · giliran ' . $project->stage['actor'] : ''),
            'Penilai Lapangan'    => $project->assigned_appraiser,
            'Tanggal Survei Terakhir' => $project->survey_date ? $tgl($project->survey_date) : null,
            'Tanggal Penilaian'   => $project->valuation_date ? $tgl($project->valuation_date) : null,
            'Nilai Diajukan'      => $project->review_submitted_at ? $tgl($project->review_submitted_at) : null,
            'Resume Disetujui'    => $project->review_approved_at ? $tgl($project->review_approved_at) : null,
            'Buku Dicetak'        => $project->printed_at ? $tgl($project->printed_at) : null,
            'Buku Ditandatangani' => $project->signed_at ? $tgl($project->signed_at) : null,
            'Buku Dikirim'        => $project->delivered_at ? $tgl($project->delivered_at) : null,
        ],
    ] as $title => $rows)
        <div class="{{ $card }} p-6">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $title }}</h2>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                @foreach ($rows as $label => $value)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="whitespace-pre-line font-medium text-gray-900 dark:text-gray-100">{{ filled($value) ? $value : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endforeach

    {{-- Objek penilaian: seluruh kolom, termasuk tanggal survei per objek. --}}
    <div class="{{ $card }} p-6">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Objek Penilaian ({{ $project->valuationObjects->count() }})</h2>
        <div class="space-y-3">
            @forelse ($project->valuationObjects as $obj)
                <div class="rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $loop->iteration }}. {{ $obj->short_label }}</p>
                    <dl class="mt-2 grid grid-cols-1 gap-x-6 gap-y-2 text-xs sm:grid-cols-2">
                        @foreach ([
                            'Lokasi'            => $obj->location,
                            'Bentuk/Jenis Hak'  => $obj->ownership_form,
                            'Atas Nama'         => $obj->owner_name,
                            'Luas Tanah'        => $obj->land_area ? $obj->land_area . ' m²' : null,
                            'Luas Bangunan'     => $obj->building_area ? $obj->building_area . ' m²' : null,
                            'Jumlah Unit'       => $obj->unit_quantity,
                            'Survei Mulai'      => $obj->survey_start_date ? $tgl($obj->survey_start_date) : null,
                            'Survei Selesai'    => $obj->survey_end_date ? $tgl($obj->survey_end_date) : null,
                            'Catatan'           => $obj->notes,
                        ] as $label => $value)
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="whitespace-pre-line font-medium text-gray-800 dark:text-gray-200">{{ filled($value) ? $value : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-500">Belum ada objek penilaian.</p>
            @endforelse
        </div>
    </div>

    {{-- Tagihan & Tanda Terima --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="{{ $card }} p-6">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Invoice &amp; Kwitansi</h2>
            <div class="space-y-2 text-sm">
                @forelse ($project->invoices->sortBy('id') as $inv)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2 last:border-0 dark:border-gray-700">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $inv->invoice_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $inv->term_description ?: $inv->invoice_type }}
                                @if ($inv->kwitansi_number) · Kwitansi {{ $inv->kwitansi_number }} @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($inv->amount) }}</p>
                            <p class="text-xs {{ $inv->status === \App\Models\Invoice::STATUS_PAID ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $inv->status }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 dark:text-gray-500">Belum ada invoice.</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $card }} p-6">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tanda Terima Pengiriman</h2>
            <div class="space-y-2 text-sm">
                @forelse ($project->deliveryReceipts as $receipt)
                    <div class="border-b border-gray-100 pb-2 last:border-0 dark:border-gray-700">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $receipt->number }} · {{ $tgl($receipt->delivery_date) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $receipt->recipient?->client_name ?: $project->effective_client_name }} —
                            @foreach ($receipt->documentRows() as $row){{ $row['label'] }} ({{ $row['qty'] }} {{ $row['unit'] }}){{ ! $loop->last ? ', ' : '' }}@endforeach
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 dark:text-gray-500">Belum ada tanda terima.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
