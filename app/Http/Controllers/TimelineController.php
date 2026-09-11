<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * TIMELINE PROJECT — Gantt mini: satu batang per proyek di sumbu tanggal,
 * dari tanggal survei sampai target Draf Laporan (survey_date + sla_draft_days
 * hari kerja). Bagian batang yang terisi = waktu yang sudah berjalan,
 * sisanya = sisa hari SLA; keterangannya ditulis di kolom kanan.
 *
 * Proyek yang belum punya survey_date / sla_draft_days tidak bisa diplot,
 * jadi ditampilkan terpisah sebagai "Belum Terjadwal" — TAPI hanya yang
 * berstatus In-Progress / Scheduled (tahap dimana survei sudah semestinya
 * dijadwalkan). Draft/DP Invoicing/Pelunasan belum relevan buat timeline
 * SLA jadi tidak perlu memenuhi daftar ini.
 */
class TimelineController extends Controller
{
    /** Jumlah label tanggal pada sumbu atas. */
    private const TICKS = 5;

    public function index(Request $request)
    {
        // Tombol "Proyek Saya" (?mine=1) — pola yang sama dengan Dashboard
        // Project: menyaring ke proyek yang penilai lapangannya user ini.
        $mine = $request->boolean('mine');

        $scoped = fn () => Project::with('instructingClient')
            ->when($mine, fn ($q) => $q->where('assigned_appraiser_id', auth()->id()));

        $plottable = $scoped()
            ->where('status', '!=', Project::STATUS_BATAL)
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

        $today = now()->startOfDay();
        $pct   = fn (Carbon $d) => max(0, min(100,
            $windowStart->diffInDays($d->copy()->startOfDay(), false) / $totalDays * 100
        ));

        $bars = $plottable->map(function (Project $p) use ($pct, $today) {
            $start = $p->survey_date->copy()->startOfDay();
            $end   = $p->estimated_completion_date->copy()->startOfDay();

            $spanDays = max(1, (int) $start->diffInDays($end));
            $elapsed  = (int) $start->diffInDays($today, false);

            $left  = $pct($start);
            $width = max(1.5, $pct($end) - $left);

            return [
                'project'   => $p,
                'left'      => round($left, 3),
                'width'     => round($width, 3),
                // Porsi waktu yang sudah berjalan; sisanya = sisa hari SLA.
                'fill'      => round(max(0, min(100, $elapsed / $spanDays * 100)), 1),
                'state'     => $p->sla_state,
                'label'     => $p->sla_label,
                'spanDays'  => $spanDays,
                'startText' => $start->translatedFormat('d M Y'),
                'endText'   => $end->translatedFormat('d M Y'),
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
            'todayPct'    => round($pct($today), 3),
            'summary'     => [
                'overdue'  => $bars->where('state', 'overdue')->count(),
                'dueSoon'  => $bars->where('state', 'due-soon')->count(),
                'onTrack'  => $bars->where('state', 'on-track')->count(),
                'done'     => $bars->where('state', 'done')->count(),
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

        $end = $plottable->reduce(
            fn (?Carbon $c, Project $p) => $c === null || $p->estimated_completion_date->gt($c)
                ? $p->estimated_completion_date->copy()->startOfDay()
                : $c
        );

        return [
            ($start->lt($today) ? $start : $today)->copy()->subDays(2),
            ($end->gt($today) ? $end : $today)->copy()->addDays(2),
        ];
    }
}
