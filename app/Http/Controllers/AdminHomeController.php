<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserBestrising;
use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;
use App\Models\Checklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminHomeController extends Controller
{
    /**
     * Hitung periode kuartal berjalan berbasis anchor (tanggal rilis).
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
        $fmt = fn (Carbon $d) => $d->translatedFormat('M Y'); // butuh locale id_ID
        $shortMonth = fn (Carbon $d) => $d->translatedFormat('M');
        $label = $start->year === $end->year
            ? sprintf('%s–%s %d', $shortMonth($start), $shortMonth($end), $end->year)
            : sprintf('%s–%s', $fmt($start), $fmt($end));

        return [$start, $end, $label];
    }

    public function index(Request $request)
    {
        // ===== Determinasi Region Aktif =====
        // 1) Utamakan region dari akun/session admin (mis. kolom id_region di tabel user)
        $sessionUser   = auth()->user();
        $sessionRegion = $sessionUser->id_region ?? (session('auth_user.id_region') ?? null);

        // 2) Kalau tidak ada region di session/akun, izinkan pilih via filter ?region_id=...
        $filterRegion    = $sessionRegion ? null : $request->query('region_id');
        $activeRegionId  = $sessionRegion ?: ($filterRegion ?: null);

        // Ambil list region untuk dropdown HANYA bila tidak ada region di session
        $regionOptions = $sessionRegion ? collect() : Region::query()
            ->orderBy('nama_region')
            ->get(['id_region','nama_region']);

        // Helper scope by region (dipakai berulang)
        $applyRegionToSerpo = function ($q) use ($activeRegionId) {
            if ($activeRegionId) { $q->where('id_region', $activeRegionId); }
            return $q;
        };
        $applyRegionToSegmen = function ($q) use ($activeRegionId) {
            if ($activeRegionId) {
                $q->whereHas('serpo', function($qq) use ($activeRegionId){
                    $qq->where('id_region', $activeRegionId);
                });
            }
            return $q;
        };
        $applyRegionToUser = function ($q) use ($activeRegionId) {
            if ($activeRegionId) { $q->where('id_region', $activeRegionId); }
            return $q;
        };

        // ---- KPI counts (scoped) ----
        $counts = [
            'users'   => (int) UserBestrising::query()->tap($applyRegionToUser)->count(),
            'regions' => (int) ($activeRegionId ? 1 : Region::query()->count()),
            'serpos'  => (int) Serpo::query()->tap($applyRegionToSerpo)->count(),
            'segmens' => (int) Segmen::query()->tap($applyRegionToSegmen)->count(),
        ];

        // ---- STATUS CHECKLIST (Approved/Pending/Rejected) ----
        $statusCounts = Checklist::query()
            ->when($activeRegionId, function($q) use ($activeRegionId){
                $q->whereHas('serpo', function($qq) use ($activeRegionId){
                    $qq->where('id_region', $activeRegionId);
                });
            })
            ->selectRaw("
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as acc,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'review admin' THEN 1 ELSE 0 END) as review_admin,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        $statusStats = [
            'acc'      => (int) ($statusCounts->acc ?? 0),
            'pending'  => (int) ($statusCounts->pending ?? 0),
            'review_admin'  => (int) ($statusCounts->review_admin ?? 0),
            'rejected' => (int) ($statusCounts->rejected ?? 0),
        ];

        // ---- Top 5 Region by jumlah Serpo ----
        if ($activeRegionId) {
            $serpoByRegion = Region::query()
                ->where('id_region', $activeRegionId)
                ->withCount(['serpos' => fn($q) => $q->where('id_region', $activeRegionId)])
                ->get(['id_region','nama_region']);
        } else {
            $serpoByRegion = Region::query()
                ->withCount('serpos')
                ->orderByDesc('serpos_count')
                ->take(5)
                ->get(['id_region','nama_region']);
        }

        // ---- Top 5 Serpo by jumlah Segmen (scoped) ----
        $segmenBySerpo = Serpo::query()
            ->tap($applyRegionToSerpo)
            ->withCount('segmens')
            ->orderByDesc('segmens_count')
            ->take(5)
            ->get(['id_serpo','nama_serpo','id_region']);

        // ---- Latest data (scoped) ----
        $latestUsers = UserBestrising::query()
            ->tap($applyRegionToUser)
            ->with('kategoriUser:id_kategoriuser,nama_kategoriuser')
            ->latest('id_userBestrising')
            ->take(5)
            ->get(['id_userBestrising','nik','nama','email','kategori_user_id','id_region']);

        $latestSerpo = Serpo::query()
            ->tap($applyRegionToSerpo)
            ->with('region:id_region,nama_region')
            ->latest('id_serpo')
            ->take(5)
            ->get(['id_serpo','nama_serpo','id_region']);

        $latestSegmen = Segmen::query()
            ->tap($applyRegionToSegmen)
            ->with(['serpo:id_serpo,nama_serpo,id_region','serpo.region:id_region,nama_region'])
            ->latest('id_segmen')
            ->take(5)
            ->get(['id_segmen','nama_segmen','id_serpo']);

        // ---- Grafik: distribusi user per kategori (scoped) ----
        $userKategori = UserBestrising::query()
            ->tap($applyRegionToUser)
            ->select('kategori_user_id', DB::raw('COUNT(*) as total'))
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
            'value' => (int) ($r->serpos_count ?? 0),
        ])->values();

        // ======= Leaderboard Points per Quarter dari Anchor (scoped ke region bila ada) =======
        $anchor = Carbon::create(2025, 9, 11);
        [$periodStart, $periodEnd, $periodLabel] = $this->currentQuarterRangeFromAnchor($anchor);

        $topSerpoPointsQuarter = Checklist::query()
            ->select('id_serpo', DB::raw('SUM(total_point) as points'))
            ->whereNotNull('id_serpo')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->when($activeRegionId, function($q) use ($activeRegionId) {
                $q->whereHas('serpo', function($qq) use ($activeRegionId){
                    $qq->where('id_region', $activeRegionId);
                });
            })
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

            'periodStart'        => $periodStart,
            'periodEnd'          => $periodEnd,
            'periodLabel'        => $periodLabel,
            'serpoPointsQuarter' => $serpoPointsQuarter,

            'sessionRegionId'    => $sessionRegion,
            'activeRegionId'     => $activeRegionId,
            'regionOptions'      => $regionOptions,

            'statusStats'        => $statusStats, // ⟵ KPI status
        ]);
    }
}
