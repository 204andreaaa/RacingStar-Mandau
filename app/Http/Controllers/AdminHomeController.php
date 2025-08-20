<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserBestrising;
use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;

class AdminHomeController extends Controller
{
    public function index()
    {
        // ---- KPI counts (pasti ada nilainya) ----
        $counts = [
            'users'   => (int) UserBestrising::query()->count(),
            'regions' => (int) Region::query()->count(),
            'serpos'  => (int) Serpo::query()->count(),
            'segmens' => (int) Segmen::query()->count(),
        ];

        // ---- Top 5 Region by jumlah Serpo (butuh relasi Region->serpos()) ----
        $serpoByRegion = Region::query()
            ->withCount('serpos')
            ->orderByDesc('serpos_count')
            ->take(5)
            ->get(['id_region','nama_region']);

        // ---- Top 5 Serpo by jumlah Segmen (butuh relasi Serpo->segmens()) ----
        $segmenBySerpo = Serpo::query()
            ->withCount('segmens')
            ->orderByDesc('segmens_count')
            ->take(5)
            ->get(['id_serpo','nama_serpo']);

        // ---- Latest data (kolom dipilih seperlunya biar irit) ----
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

        // kirim data ke view (pakai alias 'stats' juga kalau ada include yg pakai nama berbeda)
        return view('bestRising.admin.index', [
            'counts'        => $counts,   // akses di Blade: $counts['users'], dst.
            'stats'         => $counts,   // alternatif: $stats['users']
            'serpoByRegion' => $serpoByRegion,
            'segmenBySerpo' => $segmenBySerpo,
            'latestUsers'   => $latestUsers,
            'latestSerpo'   => $latestSerpo,
            'latestSegmen'  => $latestSegmen,
        ]);
    }
}
