@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 space-y-6">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $project->proposal_number }}</h1>
            <p class="text-sm text-gray-500">Dibuat {{ $project->created_at->translatedFormat('d F Y') }}</p>
        </div>
        <span class="px-3 py-1.5 rounded-full text-sm font-semibold {{ $project->status_badge_classes }}">
            {{ $project->status }}
        </span>
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
                <dt class="text-gray-500">Fee Jasa</dt>
                <dd class="font-medium text-gray-900">Rp {{ number_format($project->service_fee, 0, ',', '.') }}</dd>
            </div>
        </dl>
    </div>

    {{-- ===================== CARD DAFTAR TAGIHAN & PEMBAYARAN ===================== --}}
    @if ($project->invoices && $project->invoices->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Daftar Tagihan & Pembayaran</h2>

            <div class="divide-y divide-gray-100">
                @foreach ($project->invoices as $inv)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 text-sm">
                                Invoice {{ $inv->invoice_type }} ({{ $inv->invoice_number }})
                            </p>
                            <p class="text-xs text-gray-500">
                                Rp {{ number_format($inv->amount, 0, ',', '.') }} — 
                                <span class="{{ $inv->status === 'Paid' ? 'text-green-600 font-semibold' : 'text-orange-500' }}">
                                    {{ $inv->status }}
                                </span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @can('invoices.view')
                                {{-- Cetak Invoice --}}
                                <a href="{{ route('invoices.exportInvoice', $inv) }}" 
                                   class="px-3 py-1.5 text-xs font-medium rounded bg-gray-800 text-white hover:bg-gray-900">
                                    📄 Cetak Invoice
                                </a>

                                {{-- Tombol Kwitansi Muncul Otomatis Setiap Status Invoice = Paid --}}
                                @if ($inv->status === 'Paid')
                                    <a href="{{ route('invoices.exportKwitansi', $inv) }}" 
                                       class="px-3 py-1.5 text-xs font-medium rounded bg-teal-600 text-white hover:bg-teal-700">
                                        🧾 Cetak Kwitansi
                                    </a>
                                @endif
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===================== PANEL AKSI (GATEKEEPING PER STATUS) ===================== --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Aksi Tersedia</h2>

        {{-- ---------- STATUS: DRAFT / MENUNGGU PERSETUJUAN ---------- --}}
        @if (in_array($project->status, [\App\Models\Project::STATUS_DRAFT, \App\Models\Project::STATUS_WAITING_APPROVAL]))
            <div class="flex flex-wrap gap-3">
                @can('proposals.view')
                    <a href="{{ route('proposals.exportPdf', $project) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-800 text-white hover:bg-gray-900">
                        📄 Cetak PDF Proposal
                    </a>
                    <a href="{{ route('proposals.exportWord', $project) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-700 text-white hover:bg-blue-800">
                        📝 Unduh Word (.docx)
                    </a>
                @endcan

                @can('proposals.manage')
                    @if ($project->status === \App\Models\Project::STATUS_DRAFT)
                        <a href="{{ route('proposals.edit', $project) }}"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                            ✏️ Edit Proposal
                        </a>
                    @endif
                @endcan

                @can('invoices.manage')
                    <button type="button" onclick="openDpModal()"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        ✅ Klien Setuju (Buat Invoice DP)
                    </button>
                @endcan
            </div>

        {{-- ---------- STATUS: DP INVOICING ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_DP_INVOICING)
            @php $dpInvoice = $project->invoices->firstWhere('invoice_type', 'DP'); @endphp
            <div class="flex flex-wrap gap-3 items-center">
                @if ($dpInvoice)
                    @can('invoices.view')
                        <a href="{{ route('invoices.exportInvoice', $dpInvoice) }}"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-800 text-white hover:bg-gray-900">
                            📄 Cetak Invoice DP
                        </a>
                    @endcan

                    @if ($dpInvoice->status === 'Paid')
                        @can('invoices.view')
                            <a href="{{ route('invoices.exportKwitansi', $dpInvoice) }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-teal-600 text-white hover:bg-teal-700">
                                🧾 Cetak Kwitansi
                            </a>
                        @endcan
                        <span class="text-sm text-green-700 font-medium">✔ Invoice DP sudah Lunas.</span>
                    @else
                        @can('invoices.manage')
                            <button type="button" onclick="openVerifyPaidModal('{{ route('invoices.markAsPaid', $dpInvoice) }}')"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                                💰 Verifikasi Lunas (Keuangan)
                            </button>

                            <form action="{{ route('invoices.destroy', $dpInvoice) }}" method="POST"
                                  onsubmit="return confirm('Batalkan Invoice DP {{ $dpInvoice->invoice_number }}? Status proyek akan kembali ke Draft.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-red-300 text-red-600 hover:bg-red-50">
                                    🗑 Batalkan Invoice
                                </button>
                            </form>
                        @elsecan('invoices.view')
                            <span class="text-sm text-gray-500">Menunggu Admin Keuangan memverifikasi pembayaran.</span>
                        @endcan
                    @endif
                @else
                    <p class="text-sm text-gray-500">Invoice DP belum ditemukan.</p>
                @endif
            </div>

        {{-- ---------- STATUS: IN-PROGRESS / SCHEDULED ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_IN_PROGRESS)
            @can('survey.manage')
                @if (!$project->assigned_appraiser || !$project->survey_date)
                    {{-- Form input penilai lapangan & tanggal survei --}}
                    <form action="{{ route('projects.inputSurveyData', $project) }}" method="POST" class="space-y-4 max-w-md">
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
                        <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
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
                <div class="space-y-4 mt-2">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
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
                    </div>

                    {{-- ---------- SLA COUNTDOWN VISUAL ---------- --}}
                    <div id="sla-countdown" data-deadline="{{ $project->estimated_completion_date?->toDateString() }}"
                         class="rounded-md border p-4">
                        <div class="text-xs text-gray-500 mb-1">Sisa Waktu Menuju Deadline Draf Laporan</div>
                        <div id="sla-countdown-text" class="text-lg font-bold">Menghitung...</div>
                        <div class="text-xs text-gray-400 mt-1">
                            Target selesai: {{ $project->estimated_completion_date_formatted }}
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @can('survey.view')
                            <a href="{{ route('projects.exportSuratTugas', $project) }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-800 text-white hover:bg-gray-900">
                                📄 Cetak PDF Surat Tugas
                            </a>
                        @endcan
                        @can('invoices.manage')
                            <button type="button" onclick="openFinalInvoiceModal()"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">
                                📝 Tandai Draf Selesai (Buat Invoice Pelunasan)
                            </button>
                        @endcan
                    </div>
                </div>
            @endif

        {{-- ---------- STATUS: PELUNASAN ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_PELUNASAN)
            @php $finalInvoice = $project->invoices->firstWhere('invoice_type', 'Pelunasan'); @endphp
            <div class="space-y-4">
                <div class="flex flex-wrap gap-3 items-center">
                    @if ($finalInvoice)
                        @can('invoices.view')
                            <a href="{{ route('invoices.exportInvoice', $finalInvoice) }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-800 text-white hover:bg-gray-900">
                                📄 Cetak Invoice Pelunasan
                            </a>
                        @endcan

                        @if ($finalInvoice->status === 'Paid')
                            @can('invoices.view')
                                <a href="{{ route('invoices.exportKwitansi', $finalInvoice) }}"
                                   class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-teal-600 text-white hover:bg-teal-700">
                                    🧾 Cetak Kwitansi
                                </a>
                            @endcan
                            <span class="text-sm text-green-700 font-medium">✔ Invoice Pelunasan Lunas.</span>
                        @else
                            @can('invoices.manage')
                                <button type="button" onclick="openVerifyPaidModal('{{ route('invoices.markAsPaid', $finalInvoice) }}')"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                                    💰 Verifikasi Lunas (Keuangan)
                                </button>

                                <form action="{{ route('invoices.destroy', $finalInvoice) }}" method="POST"
                                      onsubmit="return confirm('Batalkan Invoice Pelunasan {{ $finalInvoice->invoice_number }}? Status proyek akan kembali ke In-Progress.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-red-300 text-red-600 hover:bg-red-50">
                                        🗑 Batalkan Invoice
                                    </button>
                                </form>
                            @elsecan('invoices.view')
                                <span class="text-sm text-gray-500">Menunggu Admin Keuangan memverifikasi pembayaran.</span>
                            @endcan
                        @endif
                    @else
                        <p class="text-sm text-gray-500">Invoice Pelunasan belum ditemukan.</p>
                    @endif
                </div>

                {{-- Input Nomor Laporan Resmi - TERKUNCI sampai invoice pelunasan Paid --}}
                <div class="rounded-md border border-dashed border-gray-300 p-4 bg-gray-50">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Laporan Resmi</label>
                    <div class="flex gap-2">
                        <input type="text" disabled placeholder="Terkunci — tunggu Invoice Pelunasan Paid"
                               class="flex-1 rounded-md border-gray-300 bg-gray-100 text-gray-400 shadow-sm cursor-not-allowed">
                        <button type="button" disabled
                                class="px-4 py-2 text-sm font-medium rounded-md bg-gray-300 text-gray-500 cursor-not-allowed">
                            🔒 Simpan
                        </button>
                    </div>
                </div>
            </div>

        {{-- ---------- STATUS: SELESAI ---------- --}}
        @elseif ($project->status === \App\Models\Project::STATUS_SELESAI)
            <div class="space-y-4">
                <p class="text-sm text-green-700 font-medium">✔ Seluruh tagihan lunas. Proyek selesai.</p>

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
                                    class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Simpan
                            </button>
                        </div>
                    </form>
                @elsecan('survey.view')
                    <p class="text-sm text-gray-500">Nomor Laporan Resmi: <span class="font-medium text-gray-900">{{ $project->final_report_number ?? '(belum diisi)' }}</span></p>
                @endcan
            </div>
        @endif
    </div>
</div>

{{-- ===================== MODAL 1: BUAT INVOICE DP ===================== --}}
<div id="dpModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Buat Invoice DP</h2>
            <button type="button" onclick="closeDpModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>

        <form action="{{ route('invoices.generateDp', $project) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Persentase DP (%)</label>
                <input type="number" name="dp_percentage" value="50" min="1" max="100" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Keterangan Termin (opsional)</label>
                <input type="text" name="term_description" placeholder="DP 50%"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Terbit Invoice</label>
                <input type="date" name="invoice_date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeDpModal()"
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

{{-- ===================== MODAL 3: BUAT INVOICE PELUNASAN (TANGGAL MANUAL) ===================== --}}
<div id="finalInvoiceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Buat Invoice Pelunasan</h2>
            <button type="button" onclick="closeFinalInvoiceModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('invoices.generateFinal', $project) }}" method="POST" class="space-y-3">
            @csrf
            <p class="text-sm text-gray-600">
                Ini akan menandai draf laporan selesai dan menerbitkan Invoice Pelunasan untuk sisa tagihan.
            </p>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Terbit Invoice</label>
                <input type="date" name="invoice_date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeFinalInvoiceModal()"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Batal</button>
                <button type="submit"
                        class="px-4 py-2 text-sm rounded-md bg-orange-600 text-white hover:bg-orange-700">Terbitkan Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDpModal() {
        document.getElementById('dpModal').classList.remove('hidden');
        document.getElementById('dpModal').classList.add('flex');
    }
    function closeDpModal() {
        document.getElementById('dpModal').classList.add('hidden');
        document.getElementById('dpModal').classList.remove('flex');
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

    function openFinalInvoiceModal() {
        document.getElementById('finalInvoiceModal').classList.remove('hidden');
        document.getElementById('finalInvoiceModal').classList.add('flex');
    }
    function closeFinalInvoiceModal() {
        document.getElementById('finalInvoiceModal').classList.add('hidden');
        document.getElementById('finalInvoiceModal').classList.remove('flex');
    }

    // ===================== SLA COUNTDOWN (client-side, live) =====================
    (function () {
        const box = document.getElementById('sla-countdown');
        if (!box) return;

        const deadlineStr = box.dataset.deadline;
        if (!deadlineStr) return;

        const deadline = new Date(deadlineStr + 'T23:59:59');
        const textEl = document.getElementById('sla-countdown-text');

        function render() {
            const now = new Date();
            const diffMs = deadline - now;
            const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

            if (diffDays < 0) {
                textEl.textContent = `⚠ Lewat deadline ${Math.abs(diffDays)} hari`;
                textEl.className = 'text-lg font-bold text-red-600';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-red-300 bg-red-50';
            } else if (diffDays === 0) {
                textEl.textContent = '⚠ Deadline hari ini';
                textEl.className = 'text-lg font-bold text-orange-600';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-orange-300 bg-orange-50';
            } else if (diffDays <= 2) {
                textEl.textContent = `${diffDays} hari lagi`;
                textEl.className = 'text-lg font-bold text-orange-600';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-orange-300 bg-orange-50';
            } else {
                textEl.textContent = `${diffDays} hari lagi`;
                textEl.className = 'text-lg font-bold text-green-700';
                box.className = box.className.replace(/border-\S+/g, '') + ' border-green-300 bg-green-50';
            }
        }

        render();
        setInterval(render, 60 * 60 * 1000);
    })();
</script>
@endsection