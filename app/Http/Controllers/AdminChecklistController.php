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
            $q = DB::table('activity_results as ar')
                ->leftJoin('user_bestrising as u', 'u.id_userBestrising', '=', 'ar.user_id')
                ->leftJoin('regions as r', 'r.id_region', '=', 'u.id_region')
                ->leftJoin('serpos as sp', 'sp.id_serpo', '=', 'u.id_serpo')
                ->leftJoin('segmens as sg', 'sg.id_segmen', '=', 'ar.id_segmen')
                ->leftJoin('activities as act', 'act.id', '=', 'ar.activity_id') // schema kamu: id & name
                ->select([
                    // hidden di UI
                    'ar.id',
                    'ar.checklist_id',
                    'ar.submitted_at',

                    // visible
                    'ar.status',
                    'ar.before_photo',
                    'ar.after_photo',
                    'ar.point_earned',
                    'ar.note',
                    'ar.created_at',

                    // alias tampilan
                    DB::raw('COALESCE(u.nama, u.email) as user_nama'),
                    DB::raw('act.name as activity_nama'),
                    DB::raw('COALESCE(r.nama_region, "-") as region_nama'),
                    DB::raw('COALESCE(sp.nama_serpo, "-") as serpo_nama'),
                    DB::raw('COALESCE(sg.nama_segmen, "-") as segmen_nama'),
                ]);

            // ================= FILTERS =================
            // region
            $q->when($request->filled('region'), function ($qq) use ($request) {
                $qq->where('u.id_region', (int) $request->region);
            });

            // serpo
            $q->when($request->filled('serpo'), function ($qq) use ($request) {
                $qq->where('u.id_serpo', (int) $request->serpo);
            });

            // segmen via pivot
            $q->when($request->filled('segmen'), function ($qq) use ($request) {
                $segmenId = (int) $request->segmen;
                $qq->whereExists(function ($sub) use ($segmenId) {
                    $sub->from('segmen_user_bestrising as p')
                        ->whereColumn('p.id_userBestrising', 'u.id_userBestrising')
                        ->where('p.id_segmen', $segmenId);
                });
            });

            // tanggal (submitted_at: YYYY-MM-DD)
            $q->when($request->filled('date_from') || $request->filled('date_to'), function ($qq) use ($request) {
                $from = $request->date_from;
                $to   = $request->date_to;
                if ($from && $to) {
                    $qq->whereBetween(DB::raw('DATE(ar.submitted_at)'), [$from, $to]);
                } elseif ($from) {
                    $qq->whereDate('ar.submitted_at', '>=', $from);
                } elseif ($to) {
                    $qq->whereDate('ar.submitted_at', '<=', $to);
                }
            });

            // keyword (nama/email user)
            $q->when($request->filled('keyword'), function ($qq) use ($request) {
                $kw = '%'.$request->keyword.'%';
                $qq->where(function ($w) use ($kw) {
                    $w->where('u.nama', 'like', $kw)
                    ->orWhere('u.email', 'like', $kw);
                });
            });
            // =============== END FILTERS ===============

            return DataTables::of($q)
            ->addIndexColumn()

            // --- mapping search untuk alias ---
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
            // kalau mau ikut cari di created_at (string), boleh tambah ini:
            // ->filterColumn('created_at', function ($query, $keyword) {
            //     $query->where('ar.created_at', 'like', '%'.$keyword.'%');
            // })

            ->editColumn('created_at', fn($r) => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i:s') : '-')
            ->editColumn('before_photo', function ($r) {
                if (!$r->before_photo) return '-';
                $url = asset('storage/' . ltrim($r->before_photo, '/'));
                return '<a href="'.$url.'" target="_blank"><img src="'.$url.'" class="thumb-img" alt="before"></a>';
            })
            ->editColumn('after_photo', function ($r) {
                if (!$r->after_photo) return '-';
                $url = asset('storage/' . ltrim($r->after_photo, '/'));
                return '<a href="'.$url.'" target="_blank"><img src="'.$url.'" class="thumb-img" alt="after"></a>';
            })
            ->rawColumns(['before_photo','after_photo'])
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
                ->with(['user','region','serpo','segmen']) // <-- relasi langsung di Checklist
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
