<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserBestrising;
use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;
use App\Models\Checklist;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminHomeController extends Controller
{
    /**
     * Tentukan anchor (tanggal rilis). 
     * - Bisa kamu ambil dari config/env juga kalau mau:
     *   $anchor = Carbon::parse(config('app.anchor_date', '2025-09-11'));
     */
    private function currentQuarterRangeFromAnchor(Carbon $anchor, ?Carbon $now = null): array
    {
        $now = $now ?: now();

        // Normalisasi ke awal bulan biar bersih
        $anchorStart = $anchor->copy()->startOfMonth();
        $nowStart    = $now->copy()->startOfMonth();

        // Kalau sekarang sebelum rilis, pakai periode pertama (mulai anchorStart)
        if ($nowStart->lt($anchorStart)) {
            $start = $anchorStart->copy();
            $end   = $start->copy()->addMonthsNoOverflow(2)->endOfMonth();
        } else {
            $diffMonths = $anchorStart->diffInMonths($nowStart); // non-negative
            $periodIdx  = intdiv($diffMonths, 3);                // periode ke-berapa
            $start      = $anchorStart->copy()->addMonths($periodIdx * 3);
            $end        = $start->copy()->addMonthsNoOverflow(2)->endOfMonth();
        }

        // Label periode (contoh: "Sep–Nov 2025")
        $fmt = fn (Carbon $d) => $d->translatedFormat('M Y'); // pakai locale id_ID di app biar "Sep" dst
        $shortMonth = fn (Carbon $d) => $d->translatedFormat('M');
        $label = $start->year === $end->year
            ? sprintf('%s–%s %d', $shortMonth($start), $shortMonth($end), $end->year)
            : sprintf('%s–%s', $fmt($start), $fmt($end));

        return [$start, $end, $label];
    }

    public function index()
    {
        // ---- KPI counts ----
        $counts = [
            'users'   => (int) UserBestrising::query()->count(),
            'regions' => (int) Region::query()->count(),
            'serpos'  => (int) Serpo::query()->count(),
            'segmens' => (int) Segmen::query()->count(),
        ];

        // ---- Top 5 Region by jumlah Serpo ----
        $serpoByRegion = Region::query()
            ->withCount('serpos')
            ->orderByDesc('serpos_count')
            ->take(5)
            ->get(['id_region','nama_region']);

        // ---- Top 5 Serpo by jumlah Segmen ----
        $segmenBySerpo = Serpo::query()
            ->withCount('segmens')
            ->orderByDesc('segmens_count')
            ->take(5)
            ->get(['id_serpo','nama_serpo']);

        // ---- Latest data ----
        $latestUsers = UserBestrising::query()
            ->with('kategoriUser:id_kategoriuser,nama_kategoriuser')
            ->latest('id_userBestrising')
            ->take(5)
            ->get(['id_userBestrising','nik','nama','email','kategori_user_id']);

        $latestSerpo = Serpo::query()
            ->with('region:id_region,nama_region')
            ->latest('id_serpo')
            ->take(5)
            ->get(['id_serpo','nama_serpo','id_region']);

        $latestSegmen = Segmen::query()
            ->with(['serpo:id_serpo,nama_serpo','serpo.region:id_region,nama_region'])
            ->latest('id_segmen')
            ->take(5)
            ->get(['id_segmen','nama_segmen','id_serpo']);

        // ---- Grafik: distribusi user per kategori ----
        $userKategori = UserBestrising::select('kategori_user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('kategori_user_id')
            ->with(['kategoriUser:id_kategoriuser,nama_kategoriuser'])
            ->get()
            ->map(fn ($r) => [
                'label' => $r->kategoriUser->nama_kategoriuser ?? 'Tidak diketahui',
                'value' => (int) $r->total,
            ])
            ->values();

        // ---- Grafik: serpo per region (pakai hasil $serpoByRegion) ----
        $serpoPerRegion = collect($serpoByRegion ?? [])->map(fn ($r) => [
            'label' => $r->nama_region,
            'value' => (int) $r->serpos_count,
        ])->values();

        // ================== NEW: Top Serpo by Points berdasarkan anchor ==================
        // SET ANCHOR DI SINI (tanggal rilis). Ubah sesuai kebutuhanmu:
        $anchor = Carbon::create(2025, 9, 11);
        [$periodStart, $periodEnd, $periodLabel] = $this->currentQuarterRangeFromAnchor($anchor);

        $topSerpoPointsQuarter = Checklist::query()
            ->select('id_serpo', DB::raw('SUM(total_point) as points'))
            ->whereNotNull('id_serpo')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->groupBy('id_serpo')
            ->orderByDesc('points')
            ->with(['serpo:id_serpo,nama_serpo,id_region','serpo.region:id_region,nama_region'])
            ->take(7)
            ->get();

        $serpoPointsQuarter = $topSerpoPointsQuarter->map(fn ($r) => [
            'label' => $r->serpo->nama_serpo ?? ('Serpo #'.$r->id_serpo),
            'sub'   => $r->serpo && $r->serpo->region ? $r->serpo->region->nama_region : null,
            'value' => (int) $r->points,
        ])->values();
        // ================================================================================

        return view('bestRising.admin.index', [
            'counts'             => $counts,
            'stats'              => $counts,
            'serpoByRegion'      => $serpoByRegion,
            'segmenBySerpo'      => $segmenBySerpo,
            'latestUsers'        => $latestUsers,
            'latestSerpo'        => $latestSerpo,
            'latestSegmen'       => $latestSegmen,
            'userKategori'       => $userKategori,
            'serpoPerRegion'     => $serpoPerRegion,
            // kirim data periode & leaderboard points (quarter by anchor)
            'periodStart'        => $periodStart,
            'periodEnd'          => $periodEnd,
            'periodLabel'        => $periodLabel,
            'serpoPointsQuarter' => $serpoPointsQuarter,
        ]);
    }
}
