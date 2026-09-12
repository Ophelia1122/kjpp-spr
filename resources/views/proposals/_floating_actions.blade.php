{{-- ===================== FLOATING ACTION BAR =====================
     Semua tombol aksi kontekstual proyek (dulu ada di kartu "Aksi
     Tersedia" paling bawah) dipindah ke sini (2026-09-14, feedback
     user) supaya tidak perlu scroll sampai dasar halaman tiap kali
     mau bertindak.

     Layout: VERTIKAL melayang di kanan layar (lg ke atas, ikon saja +
     tooltip saat hover) dan HORIZONTAL menempel di bawah layar pada
     mobile. Di mobile label teks tetap ditampilkan di bawah ikon —
     layar sentuh tidak punya hover sama sekali, jadi tooltip-only
     akan membuat tombol pengubah status jadi tebak-tebakan.

     Kartu "Aksi Tersedia" sekarang hanya berisi teks penjelas/catatan,
     tidak ada tombol lagi (supaya tidak ada tombol kembar). --}}
@php
    $fabActions = [];

    // ---------- ikon (Heroicons outline 24, stroke 1.7) ----------
    $fabIcons = [
        'printer'   => 'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z',
        'download'  => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3',
        'rocket'    => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
        'doc-check' => 'M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12',
        'send'      => 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5',
        'badge'     => 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z',
        'check'     => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'uturn'     => 'M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3',
    ];

    // ---------- warna per nada aksi ----------
    $fabTones = [
        'neutral' => 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white',
        'blue'    => 'text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-900/30',
        'indigo'  => 'text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-indigo-400 dark:hover:bg-indigo-900/30',
        'orange'  => 'text-orange-600 hover:bg-orange-50 hover:text-orange-700 dark:text-orange-400 dark:hover:bg-orange-900/30',
        'emerald' => 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-900/30',
        'rose'    => 'text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-900/30',
    ];

    $isActiveProject = !$project->isCancelled() && $project->status !== \App\Models\Project::STATUS_SELESAI;

    if ($isActiveProject) {
        // ---------- DRAFT / MENUNGGU PERSETUJUAN ----------
        if (in_array($project->status, [\App\Models\Project::STATUS_DRAFT, \App\Models\Project::STATUS_WAITING_APPROVAL])) {
            if (auth()->user()->can('proposals.view')) {
                $fabActions[] = [
                    'type' => 'link', 'icon' => 'printer', 'tone' => 'neutral',
                    'label' => 'Cetak PDF', 'tip' => 'Cetak proposal sebagai PDF',
                    'url' => route('proposals.exportPdf', $project),
                ];
                $fabActions[] = [
                    'type' => 'link', 'icon' => 'download', 'tone' => 'blue',
                    'label' => 'Unduh Word', 'tip' => 'Unduh proposal sebagai dokumen Word',
                    'url' => route('proposals.exportWord', $project),
                ];
            }
            if (auth()->user()->can('proposals.manage') && $project->isPaymentDeferred()) {
                $fabActions[] = [
                    'type' => 'form', 'icon' => 'rocket', 'tone' => 'indigo',
                    'label' => 'Mulai Tanpa DP', 'tip' => 'Mulai pekerjaan lapangan tanpa DP (skema Bayar Nanti)',
                    'url' => route('projects.startWithoutDp', $project),
                    'confirm' => 'Mulai pekerjaan lapangan proyek ' . $project->proposal_number . ' tanpa DP? Invoice bisa diterbitkan kapan saja setelahnya.',
                ];
            }
        }

        // ---------- IN-PROGRESS / SCHEDULED ----------
        if ($project->status === \App\Models\Project::STATUS_IN_PROGRESS
            && $project->assigned_appraiser && $project->survey_date) {

            if (auth()->user()->can('invoices.manage') && $project->isReviewApproved()) {
                $fabActions[] = [
                    'type' => 'form', 'icon' => 'doc-check', 'tone' => 'orange',
                    'label' => 'Draf Selesai', 'tip' => 'Tandai draf laporan selesai — status pindah ke Pelunasan',
                    'url' => route('projects.markDraftComplete', $project),
                    'confirm' => 'Tandai draf laporan proyek ' . $project->proposal_number . ' sudah selesai? Status akan berpindah ke Pelunasan.',
                ];
            }

            if (is_null($project->review_status)) {
                if (auth()->user()->can('survey.manage')) {
                    $fabActions[] = [
                        'type' => 'form', 'icon' => 'send', 'tone' => 'indigo',
                        'label' => 'Submit Review', 'tip' => 'Ajukan hasil pekerjaan untuk direview',
                        'url' => route('projects.review.submit', $project),
                        'confirm' => 'Ajukan hasil pekerjaan proyek ' . $project->proposal_number . ' untuk direview?',
                    ];
                }
            } elseif ($project->review_status === \App\Models\Project::REVIEW_SUBMITTED && auth()->user()->canActAsReviewer()) {
                $fabActions[] = [
                    'type' => 'form', 'icon' => 'badge', 'tone' => 'emerald',
                    'label' => 'Sudah Direview', 'tip' => 'Tandai hasil pekerjaan sudah selesai direview',
                    'url' => route('projects.review.approve', $project),
                ];
                $fabActions[] = [
                    'type' => 'modal', 'icon' => 'uturn', 'tone' => 'rose',
                    'label' => 'Ke Surveyor', 'tip' => 'Kembalikan pekerjaan ke Surveyor beserta catatan revisi',
                    'url' => route('projects.review.rejectToSurveyor', $project),
                    'modal_title' => 'Kembalikan ke Surveyor',
                ];
            } elseif ($project->review_status === \App\Models\Project::REVIEW_REVIEWED && auth()->user()->can('proposals.manage')) {
                $fabActions[] = [
                    'type' => 'form', 'icon' => 'check', 'tone' => 'emerald',
                    'label' => 'Konfirmasi', 'tip' => 'Konfirmasi pekerjaan disetujui — SLA Laporan Final mulai dihitung',
                    'url' => route('projects.review.confirm', $project),
                    'confirm' => 'Konfirmasi pekerjaan proyek ' . $project->proposal_number . ' sudah disetujui? SLA Laporan Final akan mulai dihitung.',
                ];
                $fabActions[] = [
                    'type' => 'modal', 'icon' => 'uturn', 'tone' => 'rose',
                    'label' => 'Ke Reviewer', 'tip' => 'Kembalikan pekerjaan ke Reviewer beserta catatan revisi',
                    'url' => route('projects.review.rejectToReviewer', $project),
                    'modal_title' => 'Kembalikan ke Reviewer',
                ];
            }
        }
    }
@endphp

@if (count($fabActions) > 0)
    {{-- Spacer: mencegah konten terakhir halaman tertutup bar bawah di mobile. --}}
    <div class="h-24 lg:hidden" aria-hidden="true"></div>

    <div class="fixed z-40 inset-x-0 bottom-0 lg:inset-x-auto lg:bottom-auto lg:right-4 lg:top-1/2 lg:-translate-y-1/2">
        <div class="flex flex-row items-stretch justify-center gap-1 border-t border-gray-200 bg-white/95 p-2 shadow-lg backdrop-blur
                    lg:flex-col lg:gap-1.5 lg:rounded-xl lg:border lg:p-1.5
                    dark:border-gray-700 dark:bg-gray-800/95">
            @foreach ($fabActions as $action)
                @php $btnClass = 'flex w-full flex-col items-center justify-center gap-0.5 rounded-lg px-2 py-1.5 text-[10px] font-medium leading-tight lg:h-10 lg:w-10 lg:gap-0 lg:px-0 lg:py-0 ' . ($fabTones[$action['tone']] ?? $fabTones['neutral']); @endphp
                <div class="group relative flex-1 lg:flex-none">
                    {{-- Tooltip: hanya desktop (mobile pakai label teks di bawah ikon). --}}
                    <span class="pointer-events-none absolute right-full top-1/2 z-10 mr-2 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-medium text-white shadow-lg lg:group-hover:block dark:bg-gray-900">
                        {{ $action['tip'] }}
                    </span>

                    @if ($action['type'] === 'link')
                        <a href="{{ $action['url'] }}" class="{{ $btnClass }}">
                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fabIcons[$action['icon']] }}"/>
                            </svg>
                            <span class="lg:hidden">{{ $action['label'] }}</span>
                        </a>
                    @elseif ($action['type'] === 'modal')
                        <button type="button"
                                onclick="openReviewRejectModal('{{ $action['url'] }}', '{{ $action['modal_title'] }}')"
                                class="{{ $btnClass }}">
                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fabIcons[$action['icon']] }}"/>
                            </svg>
                            <span class="lg:hidden">{{ $action['label'] }}</span>
                        </button>
                    @else
                        <form action="{{ $action['url'] }}" method="POST"
                              @if (!empty($action['confirm'])) onsubmit="return confirm('{{ $action['confirm'] }}')" @endif>
                            @csrf
                            <button type="submit" class="{{ $btnClass }}">
                                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fabIcons[$action['icon']] }}"/>
                                </svg>
                                <span class="lg:hidden">{{ $action['label'] }}</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
