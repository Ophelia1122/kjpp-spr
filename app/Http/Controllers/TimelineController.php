<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * TIMELINE PROJECT — Gantt mini: SAMPAI 3 segmen berurutan per proyek di
 * sumbu tanggal yang sama, mengikuti alur 2 SLA (lihat Project::REVIEW_*):
 *
 *   1. Segmen DRAFT   : survey_date -> estimated_completion_date (target
 *      SLA Draf/Resume). Aktif & dua-tona (elapsed/sisa) selama surveyor
 *      BELUM submit untuk review; begitu review_status terisi (submitted/
 *      reviewed/approved) segmen ini "dikunci" jadi abu-abu solid (riwayat
 *      — surveyor sudah selesai, tak perlu terus mengejar target lagi).
 *   2. Segmen GAP      : review_submitted_at -> review_approved_at (atau
 *      hari ini kalau belum dikonfirmasi) — waktu proses Reviewer & Admin
 *      Produksi yang TIDAK terhitung SLA manapun. Digambar netral (abu-abu
 *      putus-putus) supaya jelas itu bukan bug/lubang, cuma proses berjalan.
 *      Hanya muncul kalau proyek pernah disubmit (review_status !== null).
 *   3. Segmen FINAL    : review_approved_at -> estimated_final_completion_date
 *      (target SLA Laporan Final). Muncul HANYA setelah Admin Produksi
 *      konfirmasi (review_approved_at terisi) — sebelum itu tanggalnya
 *      memang belum bisa diketahui. Pakai dua-tona + perpanjangan putus-
 *      putus kalau lewat deadline, identik dengan segmen Draft.
 *
 * Kalau proyek DIKEMBALIKAN ke Surveyor (rejectReviewToSurveyor), review_
 * status di-null-kan lagi -> segmen Draft otomatis kembali AKTIF (bukan
 * abu-abu) walau review_submitted_at lama masih tersimpan, jadi deteksi
 * "draft selesai" pakai Project::isReviewSubmitted() (cek review_status),
 * BUKAN cek review_submitted_at langsung.
 *
 * "state"/"label" pada tiap $bar merepresentasikan FASE YANG SEDANG AKTIF
 * (Final kalau sudah confirmed, kalau belum ya Draft) — dipakai utk warna
 * & teks kolom kanan, dan ringkasan Lewat deadline/Mendekati/On-track di
 * atas supaya menghitung fase yang relevan, bukan selalu Draft.
 *
 * Proyek yang belum punya survey_date / sla_draft_days tidak bisa diplot,
 * jadi ditampilkan terpisah sebagai "Belum Terjadwal" — TAPI hanya yang
 * berstatus In-Progress / Scheduled (tahap dimana survei sudah semestinya
 * dijadwalkan). Draft/DP Invoicing/Pelunasan belum relevan buat timeline
 * SLA jadi tidak perlu memenuhi daftar ini.
 *
 * Proyek "Selesai" SENGAJA tidak ikut diplot sama sekali (pakai
 * Project::scopeActive()) — timeline ini murni utk memantau proyek yang
 * SEDANG berjalan; proyek yang sudah kelar tak lagi relevan dipantau
 * SLA-nya di sini, dan kalau diikutkan akan terus menumpuk + melebarkan
 * skala sumbu tanggal seiring waktu. Riwayat proyek selesai ada di
 * Dashboard Project.
 */
class TimelineController extends Controller
{
    /** Jumlah label tanggal pada sumbu atas. */
    private const TICKS = 5;

    public function index(Request $request)
    {
        // Tombol "Proyek Saya" (?mine=1) — pola yang sama dengan Dashboard
        // Project: menyaring ke proyek yang penilai lapangannya user ini.
        // Default (kalau parameter `mine` sama sekali tak ada di URL): user
        // berjabatan Penilai/Pelaksana Inspeksi diutamakan lihat "Proyek
        // Saya" duluan — kerjaan lapangan hariannya cuma proyek yang jadi
        // tanggung jawabnya. Jabatan lain tetap default "Semua Proyek".
        // Pill "Semua Proyek" kirim `mine=0` eksplisit (lihat view), jadi
        // klik eksplisit tetap dihormati.
        $mine = $request->has('mine')
            ? $request->boolean('mine')
            : in_array(auth()->user()->jabatan, [
                \App\Models\User::JABATAN_PENILAI,
                \App\Models\User::JABATAN_PELAKSANA_INSPEKSI,
            ], true);

        // Role admin tidak punya tab "Proyek Saya" — selalu Semua Proyek
        // (2026-09-14, disamakan dengan List Project).
        if (auth()->user()->seesOfficeWide()) {
            $mine = false;
        }

        $scoped = fn () => Project::with('instructingClient')
            ->when($mine, fn ($q) => $q->where('assigned_appraiser_id', auth()->id()));

        $plottable = $scoped()
            ->active()
            ->whereNotNull('survey_date')
            ->whereNotNull('sla_draft_days')
            ->get()
            ->filter(fn ($p) => $p->estimated_completion_date !== null)
            ->sortBy(fn ($p) => $p->survey_date->timestamp)
            ->values();

        $unscheduled = $scoped()
            ->where('status', Project::STATUS_IN_PROGRESS)
            ->where(function ($q) {
                $q->whereNull('survey_date')->orWhereNull('sla_draft_days');
            })
            ->latest()
            ->get();

        [$windowStart, $windowEnd] = $this->window($plottable);
        $totalDays = max(1, (int) $windowStart->diffInDays($windowEnd));

        $today    = now()->startOfDay();
        $pct      = fn (Carbon $d) => max(0, min(100,
            $windowStart->diffInDays($d->copy()->startOfDay(), false) / $totalDays * 100
        ));
        $todayPct = round($pct($today), 3);

        $bars = $plottable->map(function (Project $p) use ($pct, $today, $todayPct) {
            $start = $p->survey_date->copy()->startOfDay();
            $end   = $p->estimated_completion_date->copy()->startOfDay();

            $spanDays = max(1, (int) $start->diffInDays($end));
            $elapsed  = (int) $start->diffInDays($today, false);

            $left  = $pct($start);
            $width = max(1.5, $pct($end) - $left);

            $draftDone = $p->isReviewSubmitted();

            // Kalau lewat deadline SAAT MASIH AKTIF, batang "menjorok"
            // melewati tanggal target sampai hari ini — supaya keterlambatan
            // kelihatan dari BENTUK batangnya, bukan cuma teks di samping.
            // Begitu draft "dikunci" (sudah disubmit), tak perlu lagi —
            // bagian surveyor sudah selesai, tak sedang mengejar apa pun.
            $overdueWidth = (! $draftDone && $p->sla_state === 'overdue')
                ? max(0, round($todayPct - ($left + $width), 3))
                : 0;

            // --- Segmen GAP: waktu proses review (submit -> approve/hari ini) ---
            $gap = null;
            if ($draftDone && $p->review_submitted_at) {
                $gapStart    = $p->review_submitted_at->copy()->startOfDay();
                $gapEndDate  = $p->review_approved_at ? $p->review_approved_at->copy()->startOfDay() : $today;
                $gapLeft     = $pct($gapStart);
                $gap = [
                    'left'      => round($gapLeft, 3),
                    'width'     => max(0.8, round($pct($gapEndDate) - $gapLeft, 3)),
                    'startText' => $gapStart->translatedFormat('d M Y'),
                    'endText'   => $gapEndDate->translatedFormat('d M Y'),
                ];
            }

            // --- Segmen FINAL: hanya ada setelah Admin Produksi konfirmasi ---
            $final = null;
            if ($p->isReviewApproved() && $p->estimated_final_completion_date) {
                $fStart = $p->review_approved_at->copy()->startOfDay();
                $fEnd   = $p->estimated_final_completion_date->copy()->startOfDay();

                $fSpanDays = max(1, (int) $fStart->diffInDays($fEnd));
                $fElapsed  = (int) $fStart->diffInDays($today, false);

                $fLeft  = $pct($fStart);
                $fWidth = max(1.5, $pct($fEnd) - $fLeft);

                $final = [
                    'left'         => round($fLeft, 3),
                    'width'        => round($fWidth, 3),
                    'overdueWidth' => $p->final_sla_state === 'overdue'
                        ? max(0, round($todayPct - ($fLeft + $fWidth), 3))
                        : 0,
                    'fill'         => round(max(0, min(100, $fElapsed / $fSpanDays * 100)), 1),
                    'state'        => $p->final_sla_state,
                    'label'        => $p->final_sla_label,
                    'spanDays'     => $fSpanDays,
                    'startText'    => $fStart->translatedFormat('d M Y'),
                    'endText'      => $fEnd->translatedFormat('d M Y'),
                ];
            }

            return [
                'project'      => $p,
                'left'         => round($left, 3),
                'width'        => round($width, 3),
                'overdueWidth' => $overdueWidth,
                'draftDone'    => $draftDone,
                // Porsi waktu yang sudah berjalan; sisanya = sisa hari SLA.
                // (Tak dipakai lagi kalau draftDone — segmen jadi solid abu-abu.)
                'fill'         => round(max(0, min(100, $elapsed / $spanDays * 100)), 1),
                'gap'          => $gap,
                'final'        => $final,
                // Fase yg SEDANG AKTIF (Final kalau sudah confirmed, kalau
                // belum ya Draft) — dipakai kolom kanan & ringkasan atas.
                'state'        => $final ? $final['state'] : $p->sla_state,
                'label'        => $final ? $final['label'] : $p->sla_label,
                'spanDays'     => $spanDays,
                'startText'    => $start->translatedFormat('d M Y'),
                'startShort'   => $start->translatedFormat('d M'),
                // Target Draft sendiri (dipakai tooltip segmen Draft) — beda
                // dg 'endText' di bawah yg mengikuti fase AKTIF utk kolom kanan.
                'draftEndText' => $end->translatedFormat('d M Y'),
                'endText'      => $final ? $final['endText'] : $end->translatedFormat('d M Y'),
            ];
        });

        $ticks = collect(range(0, self::TICKS))->map(function ($i) use ($windowStart, $totalDays) {
            $offset = (int) round($totalDays * $i / self::TICKS);

            return [
                'pct'   => round($offset / $totalDays * 100, 3),
                'label' => $windowStart->copy()->addDays($offset)->translatedFormat('d M'),
            ];
        });

        return view('timeline.index', [
            'bars'        => $bars,
            'unscheduled' => $unscheduled,
            'ticks'       => $ticks,
            'mine'        => $mine,
            'todayPct'    => $todayPct,
            'summary'     => [
                'overdue'  => $bars->where('state', 'overdue')->count(),
                'dueSoon'  => $bars->where('state', 'due-soon')->count(),
                'onTrack'  => $bars->where('state', 'on-track')->count(),
            ],
        ]);
    }

    /**
     * Rentang sumbu tanggal: mencakup seluruh batang DAN hari ini,
     * diberi jeda 2 hari di kiri-kanan supaya batang tidak menempel tepi.
     */
    private function window($plottable): array
    {
        $today = now()->startOfDay();

        if ($plottable->isEmpty()) {
            return [$today->copy()->subDays(7), $today->copy()->addDays(21)];
        }

        $start = $plottable->reduce(
            fn (?Carbon $c, Project $p) => $c === null || $p->survey_date->lt($c)
                ? $p->survey_date->copy()->startOfDay()
                : $c
        );

        // Batas akhir sumbu: target Draf, ATAU target Final kalau proyeknya
        // sudah masuk fase itu (bisa lebih jauh dari target Draf).
        $end = $plottable->reduce(function (?Carbon $c, Project $p) {
            $candidate = $p->estimated_completion_date->copy()->startOfDay();

            if ($p->isReviewApproved() && $p->estimated_final_completion_date) {
                $finalEnd = $p->estimated_final_completion_date->copy()->startOfDay();
                if ($finalEnd->gt($candidate)) {
                    $candidate = $finalEnd;
                }
            }

            return $c === null || $candidate->gt($c) ? $candidate : $c;
        });

        return [
            ($start->lt($today) ? $start : $today)->copy()->subDays(2),
            ($end->gt($today) ? $end : $today)->copy()->addDays(2),
        ];
    }
}
