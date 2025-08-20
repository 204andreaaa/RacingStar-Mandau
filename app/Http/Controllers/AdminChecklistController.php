<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ActivityResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;

class AdminChecklistController extends Controller
{
    // LIST semua sesi (server-side DataTables)
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $q = \DB::table('checklists as c')
                ->leftJoin('user_bestrising as u', 'u.id_userBestrising', '=', 'c.user_id')
                ->leftJoin('regions as r', 'r.id_region', '=', 'c.id_region')
                ->leftJoin('serpos  as s', 's.id_serpo',   '=', 'c.id_serpo')
                ->leftJoin('segmens as g', 'g.id_segmen',  '=', 'c.id_segmen')
                ->select([
                    'c.id','c.team','c.status','c.total_point','c.started_at','c.submitted_at',
                    'u.nama as user_nama',
                    'r.nama_region','s.nama_serpo','g.nama_segmen',
                ])
                ->orderByDesc('c.id');

            // filter custom dari dropdown (opsional)
            if ($request->team)   $q->where('c.team', $request->team);
            if ($request->status) $q->where('c.status', $request->status);
            if ($request->date_from) $q->whereDate('c.started_at', '>=', $request->date_from);
            if ($request->date_to)   $q->whereDate('c.started_at', '<=', $request->date_to);

            return \Yajra\DataTables\Facades\DataTables::of($q)
                ->addIndexColumn()
                ->editColumn('started_at', fn($r) => $r->started_at ? date('Y-m-d H:i', strtotime($r->started_at)) : '-')
                ->editColumn('submitted_at', fn($r) => $r->submitted_at ? date('Y-m-d H:i', strtotime($r->submitted_at)) : '-')
                ->addColumn('lokasi', fn($r) => "{$r->nama_region} / {$r->nama_serpo} / {$r->nama_segmen}")
                ->addColumn('action', function($r){
                    $detail = '<a href="'.route('admin.checklists.show',$r->id).'" class="btn btn-sm btn-outline-primary me-1">Detail</a>';
                    $del    = '<button class="btn btn-sm btn-outline-danger btn-del" data-id="'.$r->id.'">Delete</button>';
                    return $detail.$del;
                })
                ->rawColumns(['action'])

                // ⬇️ ini yang penting: arahkan search 'user_nama' ke 'u.nama'
                ->filterColumn('user_nama', function($query, $keyword) {
                    $query->where('u.nama', 'like', "%{$keyword}%");
                })
                // (opsional) biar search global juga bisa cari region/serpo/segmen:
                ->filterColumn('lokasi', function($query, $keyword) {
                    $query->where(function($q) use ($keyword){
                        $q->where('r.nama_region','like',"%{$keyword}%")
                        ->orWhere('s.nama_serpo','like',"%{$keyword}%")
                        ->orWhere('g.nama_segmen','like',"%{$keyword}%");
                    });
                })

                ->make(true);
        }

        return view('bestRising.admin.checklists.index');
    }


    // DETAIL satu sesi
    public function show(Checklist $checklist)
    {
        // meta (join untuk nama)
        $meta = DB::table('checklists as c')
            ->leftJoin('user_bestrising as u', 'u.id_userBestrising', '=', 'c.user_id')
            ->leftJoin('regions as r', 'r.id_region', '=', 'c.id_region')
            ->leftJoin('serpos  as s', 's.id_serpo',   '=', 'c.id_serpo')
            ->leftJoin('segmens as g', 'g.id_segmen',  '=', 'c.id_segmen')
            ->where('c.id', $checklist->id)
            ->select([
                'c.*','u.nama as user_nama','r.nama_region','s.nama_serpo','g.nama_segmen'
            ])->first();

        // items (aktivitas yang diceklis)
        $items = ActivityResult::with('activity')
            ->where('checklist_id', $checklist->id)
            ->orderByDesc('submitted_at')
            ->get();

        return view('bestRising.admin.checklists.show', compact('checklist','meta','items'));
    }

    public function destroy(Checklist $checklist)
    {
        // ambil semua activity result
        $results = ActivityResult::where('checklist_id', $checklist->id)->get();

        foreach ($results as $res) {
            // hapus file before kalau ada
            if ($res->before_photo) {
                Storage::disk('public')->delete($res->before_photo);
            }
            // hapus file after kalau ada
            if ($res->after_photo) {
                Storage::disk('public')->delete($res->after_photo);
            }
        }

        // hapus semua activity result dari DB
        ActivityResult::where('checklist_id', $checklist->id)->delete();

        // hapus checklist dari DB
        $checklist->delete();

        return response()->json(['message' => 'Checklist + foto berhasil dihapus.']);
    }
}
