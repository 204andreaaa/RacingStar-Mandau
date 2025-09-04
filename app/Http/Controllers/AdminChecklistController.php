<?php

namespace App\Http\Controllers;

use App\Exports\ChecklistsExport;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exports\AllResultsExport;

class AdminChecklistController extends Controller
{
    public function allresult(Request $request)
    {
        if ($request->ajax()) {
            // Kumpulkan semua foto per activity_result: before & after
            $beforeListSub = DB::table('activity_result_photos')
                ->selectRaw('activity_result_id, GROUP_CONCAT(path ORDER BY id ASC SEPARATOR "|") as before_list')
                ->where('kind', 'before')
                ->groupBy('activity_result_id');

            $afterListSub = DB::table('activity_result_photos')
                ->selectRaw('activity_result_id, GROUP_CONCAT(path ORDER BY id ASC SEPARATOR "|") as after_list')
                ->where('kind', 'after')
                ->groupBy('activity_result_id');

            $q = DB::table('activity_results as ar')
                ->leftJoin('user_bestrising as u', 'u.id_userBestrising', '=', 'ar.user_id')
                ->leftJoin('regions as r', 'r.id_region', '=', 'u.id_region')
                ->leftJoin('serpos as sp', 'sp.id_serpo', '=', 'u.id_serpo')
                ->leftJoin('segmens as sg', 'sg.id_segmen', '=', 'ar.id_segmen')
                ->leftJoin('activities as act', 'act.id', '=', 'ar.activity_id')
                ->leftJoinSub($beforeListSub, 'bfl', 'bfl.activity_result_id', '=', 'ar.id')
                ->leftJoinSub($afterListSub,  'afl', 'afl.activity_result_id', '=', 'ar.id')
                ->select([
                    // hidden di UI
                    'ar.id',
                    'ar.checklist_id',
                    'ar.submitted_at',

                    // visible
                    'ar.status',
                    'ar.point_earned',
                    'ar.note',
                    'ar.created_at',

                    // alias tampilan
                    DB::raw('COALESCE(u.nama, u.email) as user_nama'),
                    DB::raw('act.name as activity_nama'),
                    DB::raw('COALESCE(r.nama_region, "-") as region_nama'),
                    DB::raw('COALESCE(sp.nama_serpo, "-") as serpo_nama'),
                    DB::raw('COALESCE(sg.nama_segmen, "-") as segmen_nama'),

                    // list foto (akan dirender di 2 kolom terpisah)
                    DB::raw('bfl.before_list'),
                    DB::raw('afl.after_list'),
                ]);

            // ================= FILTERS =================
            if ($request->filled('region')) {
                $q->where('u.id_region', (int) $request->region);
            }
            if ($request->filled('serpo')) {
                $q->where('u.id_serpo', (int) $request->serpo);
            }
            if ($request->filled('segmen')) {
                $segmenId = (int) $request->segmen;
                $q->whereExists(function ($sub) use ($segmenId) {
                    $sub->from('segmen_user_bestrising as p')
                        ->whereColumn('p.id_userBestrising', 'u.id_userBestrising')
                        ->where('p.id_segmen', $segmenId);
                });
            }
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $from = $request->date_from;
                $to   = $request->date_to;
                if ($from && $to) {
                    $q->whereBetween(DB::raw('DATE(ar.submitted_at)'), [$from, $to]);
                } elseif ($from) {
                    $q->whereDate('ar.submitted_at', '>=', $from);
                } elseif ($to) {
                    $q->whereDate('ar.submitted_at', '<=', $to);
                }
            }
            if ($request->filled('keyword')) {
                $kw = '%'.$request->keyword.'%';
                $q->where(function ($w) use ($kw) {
                    $w->where('u.nama','like',$kw)
                      ->orWhere('u.email','like',$kw)
                      ->orWhere('act.name','like',$kw)
                      ->orWhere('r.nama_region','like',$kw)
                      ->orWhere('sp.nama_serpo','like',$kw);
                });
            }
            // =============== END FILTERS ===============

            $q->orderByDesc('ar.created_at');

            return DataTables::of($q)
                ->addIndexColumn()

                // mapping search untuk alias
                ->filterColumn('user_nama', function ($query, $keyword) {
                    $kw = '%'.$keyword.'%';
                    $query->where(function($w) use ($kw){
                        $w->where('u.nama', 'like', $kw)
                          ->orWhere('u.email', 'like', $kw);
                    });
                })
                ->filterColumn('activity_nama', function ($query, $keyword) {
                    $query->where('act.name', 'like', '%'.$keyword.'%');
                })
                ->filterColumn('region_nama', function ($query, $keyword) {
                    $query->where('r.nama_region', 'like', '%'.$keyword.'%');
                })
                ->filterColumn('serpo_nama', function ($query, $keyword) {
                    $query->where('sp.nama_serpo', 'like', '%'.$keyword.'%');
                })

                ->editColumn('created_at', fn($r) => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i:s') : '-')

                // Kolom BEFORE
                ->addColumn('before_photos', function ($r) {
                    if (!$r->before_list) return '-';
                    $pieces = [];
                    foreach (explode('|', $r->before_list) as $path) {
                        if (!$path) continue;
                        $url = asset('storage/' . ltrim($path,'/'));
                        $pieces[] =
                            '<a href="'.$url.'" target="_blank" class="photo-item" title="Before">'.
                              '<img src="'.$url.'" class="thumb-img" alt="before">'.
                              '<span class="photo-badge">B</span>'.
                            '</a>';
                    }
                    return '<div class="photo-scroller">'.$pieces[0].implode('', array_slice($pieces,1)).'</div>';
                })

                // Kolom AFTER
                ->addColumn('after_photos', function ($r) {
                    if (!$r->after_list) return '-';
                    $pieces = [];
                    foreach (explode('|', $r->after_list) as $path) {
                        if (!$path) continue;
                        $url = asset('storage/' . ltrim($path,'/'));
                        $pieces[] =
                            '<a href="'.$url.'" target="_blank" class="photo-item" title="After">'.
                              '<img src="'.$url.'" class="thumb-img" alt="after">'.
                              '<span class="photo-badge">A</span>'.
                            '</a>';
                    }
                    return '<div class="photo-scroller">'.$pieces[0].implode('', array_slice($pieces,1)).'</div>';
                })

                ->rawColumns(['before_photos','after_photos'])
                ->make(true);
        }

        return view('bestRising.admin.checklists.allresult');
    }

    public function exportAllResult(Request $request)
    {
        $filters  = $request->only(['region','serpo','segmen','date_from','date_to','keyword']);
        $filename = 'all-results-'.now()->format('Ymd_His').'.xlsx';
        return Excel::download(new AllResultsExport($filters), $filename);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $q = Checklist::query()
                ->with(['user','region','serpo','segmen'])
                ->when($request->team,      fn($qq) => $qq->where('team', $request->team))
                ->when($request->status,    fn($qq) => $qq->where('status', $request->status))
                ->when($request->date_from, fn($qq) => $qq->whereDate('started_at', '>=', $request->date_from))
                ->when($request->date_to,   fn($qq) => $qq->whereDate('started_at', '<=', $request->date_to))
                ->orderByDesc('started_at');

            return DataTables::of($q)
                ->addIndexColumn()
                ->editColumn('started_at',   fn($row) => optional($row->started_at)->format('Y-m-d H:i:s'))
                ->editColumn('submitted_at', fn($row) => optional($row->submitted_at)->format('Y-m-d H:i:s'))
                ->addColumn('user_nama',     fn($row) => $row->user->nama ?? '-')
                ->addColumn('lokasi', function($row){
                    $region = $row->region->nama_region ?? '-';
                    $serpo  = $row->serpo->nama_serpo   ?? '-';
                    return "{$region} / {$serpo}";
                })
                ->addColumn('action', function($row){
                    $btnShow = '<a class="btn btn-sm btn-outline-primary mr-1" href="'.route('admin.checklists.show', $row->id).'">Detail</a>';
                    $btnDel = '<button class="btn btn-sm btn-outline-danger btn-del" data-url="'.route('admin.checklists.destroy', $row->id).'">Hapus</button>';
                    return $btnShow.' '.$btnDel;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('bestRising.admin.checklists.index');
    }

    public function show($id)
    {
        $checklist = Checklist::with(['user','region','serpo','segmen','items'])->findOrFail($id);
        return view('bestRising.admin.checklists.show', compact('checklist'));
    }

    public function destroy($id)
    {
        $checklist = Checklist::findOrFail($id);
        if (method_exists($checklist, 'items')) {
            $checklist->items()->delete();
        }
        $checklist->delete();

        return response()->json(['success'=>true,'message'=>'Checklist dihapus.']);
    }

    /** Export Excel: ikut filter & search global, ambil semua data (no pagination) */
    public function export(Request $req)
    {
        $filename = 'checklists_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new ChecklistsExport(
                team:     $req->input('team'),
                status:   $req->input('status'),
                dateFrom: $req->input('date_from'),
                dateTo:   $req->input('date_to'),
                search:   $req->input('search')
            ),
            $filename
        );
    }
}
