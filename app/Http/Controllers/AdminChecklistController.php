<?php

namespace App\Http\Controllers;

use App\Exports\AllResultsExport;
use App\Exports\ChecklistsExport;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;   // ← tambah: cek kolom legacy
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class AdminChecklistController extends Controller
{
    public function allresult(Request $request)
    {
        if ($request->ajax()) {
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
                    'ar.id','ar.checklist_id','ar.submitted_at',
                    'ar.status','ar.point_earned','ar.note','ar.created_at',
                    DB::raw('COALESCE(u.nama, u.email) as user_nama'),
                    DB::raw('act.name as activity_nama'),
                    DB::raw('COALESCE(r.nama_region, "-") as region_nama'),
                    DB::raw('COALESCE(sp.nama_serpo, "-") as serpo_nama'),
                    DB::raw('COALESCE(sg.nama_segmen, "-") as segmen_nama'),
                    DB::raw('bfl.before_list'),
                    DB::raw('afl.after_list'),
                ]);

            if ($request->filled('region')) $q->where('u.id_region', (int) $request->region);
            if ($request->filled('serpo'))  $q->where('u.id_serpo',  (int) $request->serpo);
            if ($request->filled('segmen')) {
                $segmenId = (int) $request->segmen;
                $q->whereExists(function ($sub) use ($segmenId) {
                    $sub->from('segmen_user_bestrising as p')
                        ->whereColumn('p.id_userBestrising', 'u.id_userBestrising')
                        ->where('p.id_segmen', $segmenId);
                });
            }
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $from = $request->date_from; $to = $request->date_to;
                if ($from && $to) $q->whereBetween(DB::raw('DATE(ar.submitted_at)'), [$from, $to]);
                elseif ($from)   $q->whereDate('ar.submitted_at', '>=', $from);
                elseif ($to)     $q->whereDate('ar.submitted_at', '<=', $to);
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

            $q->orderByDesc('ar.created_at');

            return DataTables::of($q)
                ->addIndexColumn()
                ->filterColumn('user_nama', function ($query, $keyword) {
                    $kw = '%'.$keyword.'%';
                    $query->where(function($w) use ($kw){
                        $w->where('u.nama','like',$kw)->orWhere('u.email','like',$kw);
                    });
                })
                ->filterColumn('activity_nama', fn($query,$k) => $query->where('act.name','like','%'.$k.'%'))
                ->filterColumn('region_nama', fn($query,$k) => $query->where('r.nama_region','like','%'.$k.'%'))
                ->filterColumn('serpo_nama',  fn($query,$k) => $query->where('sp.nama_serpo','like','%'.$k.'%'))
                ->editColumn('created_at', fn($r) => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i:s') : '-')
                ->addColumn('before_photos', function ($r) {
                    if (!$r->before_list) return '-';
                    $pieces = [];
                    foreach (explode('|', $r->before_list) as $path) {
                        if (!$path) continue;
                        $url = asset('storage/'.ltrim($path,'/'));
                        $pieces[] = '<a href="'.$url.'" target="_blank" class="photo-item" title="Before"><img src="'.$url.'" class="thumb-img" alt="before"><span class="photo-badge">B</span></a>';
                    }
                    return '<div class="photo-scroller">'.$pieces[0].implode('', array_slice($pieces,1)).'</div>';
                })
                ->addColumn('after_photos', function ($r) {
                    if (!$r->after_list) return '-';
                    $pieces = [];
                    foreach (explode('|', $r->after_list) as $path) {
                        if (!$path) continue;
                        $url = asset('storage/'.ltrim($path,'/'));
                        $pieces[] = '<a href="'.$url.'" target="_blank" class="photo-item" title="After"><img src="'.$url.'" class="thumb-img" alt="after"><span class="photo-badge">A</span></a>';
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
                    $btnDel  = '<button class="btn btn-sm btn-outline-danger btn-del" data-url="'.route('admin.checklists.destroy', $row->id).'">Hapus</button>';
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

    /** Hapus SATU checklist + semua hasil & file fotonya */
    public function destroy($id)
    {
        $checklist = Checklist::findOrFail($id);

        $activityResultIds = DB::table('activity_results')
            ->where('checklist_id', $checklist->id)
            ->pluck('id')
            ->all();

        // kumpulkan path foto (dari tabel photos)
        $photoPaths = [];
        if (!empty($activityResultIds)) {
            $photoPaths = DB::table('activity_result_photos')
                ->whereIn('activity_result_id', $activityResultIds)
                ->pluck('path')
                ->filter()
                ->map(fn($p) => ltrim($p,'/'))
                ->all();

            // tambahkan legacy hanya jika kolomnya ada
            $hasBefore = Schema::hasColumn('activity_results','before_photo');
            $hasAfter  = Schema::hasColumn('activity_results','after_photo');
            if ($hasBefore || $hasAfter) {
                $sel = [];
                if ($hasBefore) $sel[] = 'before_photo';
                if ($hasAfter)  $sel[] = 'after_photo';

                $legacy = DB::table('activity_results')
                    ->whereIn('id', $activityResultIds)
                    ->select($sel)
                    ->get();

                foreach ($legacy as $row) {
                    if ($hasBefore && !empty($row->before_photo)) $photoPaths[] = ltrim($row->before_photo,'/');
                    if ($hasAfter  && !empty($row->after_photo))  $photoPaths[] = ltrim($row->after_photo,'/');
                }
            }
        }

        DB::beginTransaction();
        try {
            foreach (array_unique($photoPaths) as $relPath) {
                if (Storage::disk('public')->exists($relPath)) {
                    Storage::disk('public')->delete($relPath);
                }
            }

            if (!empty($activityResultIds)) {
                DB::table('activity_result_photos')->whereIn('activity_result_id', $activityResultIds)->delete();
                DB::table('activity_results')->whereIn('id', $activityResultIds)->delete();
            }

            if (method_exists($checklist, 'items')) $checklist->items()->delete();
            $checklist->delete();

            DB::commit();
            return response()->json(['success'=>true,'message'=>'Checklist & semua foto terkait dihapus.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Gagal menghapus: '.$e->getMessage()], 500);
        }
    }

    /** Export Excel */
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

    /**
     * HAPUS MASSAL sesuai filter + bersihkan semua file foto (public disk).
     */
    public function destroyAll(Request $request)
    {
        $team     = $request->input('team');
        $status   = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $search   = $request->input('search');

        $checklistQuery = Checklist::query()
            ->when($team,     fn($q) => $q->where('team', $team))
            ->when($status,   fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('started_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('started_at', '<=', $dateTo));

        if ($search) {
            $kw = '%'.$search.'%';
            $checklistQuery->where(function($qq) use ($kw) {
                $qq->whereHas('user',   fn($u) => $u->where('nama','like',$kw)->orWhere('email','like',$kw))
                   ->orWhereHas('region',fn($r) => $r->where('nama_region','like',$kw))
                   ->orWhereHas('serpo', fn($s) => $s->where('nama_serpo','like',$kw))
                   ->orWhere('team','like',$kw)
                   ->orWhere('status','like',$kw);
            });
        }

        $checklists = $checklistQuery->get(['id']);
        if ($checklists->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada data yang cocok dengan filter.',
                'deleted' => ['checklists'=>0, 'results'=>0, 'photos'=>0, 'files'=>0],
            ]);
        }

        $checklistIds = $checklists->pluck('id')->all();

        $activityResultIds = DB::table('activity_results')
            ->whereIn('checklist_id', $checklistIds)
            ->pluck('id')
            ->all();

        $photoPaths = [];
        if (!empty($activityResultIds)) {
            $photoPaths = DB::table('activity_result_photos')
                ->whereIn('activity_result_id', $activityResultIds)
                ->pluck('path')
                ->filter()
                ->map(fn($p) => ltrim($p,'/'))
                ->all();

            // cek kolom legacy
            $hasBefore = Schema::hasColumn('activity_results','before_photo');
            $hasAfter  = Schema::hasColumn('activity_results','after_photo');
            if ($hasBefore || $hasAfter) {
                $sel = [];
                if ($hasBefore) $sel[] = 'before_photo';
                if ($hasAfter)  $sel[] = 'after_photo';

                $legacy = DB::table('activity_results')
                    ->whereIn('id', $activityResultIds)
                    ->select($sel)
                    ->get();

                foreach ($legacy as $row) {
                    if ($hasBefore && !empty($row->before_photo)) $photoPaths[] = ltrim($row->before_photo,'/');
                    if ($hasAfter  && !empty($row->after_photo))  $photoPaths[] = ltrim($row->after_photo,'/');
                }
            }
        }

        $deleted = ['checklists'=>0, 'results'=>0, 'photos'=>0, 'files'=>0];

        DB::beginTransaction();
        try {
            $deletedFiles = 0;
            foreach (array_unique($photoPaths) as $relPath) {
                if (Storage::disk('public')->exists($relPath) && Storage::disk('public')->delete($relPath)) {
                    $deletedFiles++;
                }
            }

            if (!empty($activityResultIds)) {
                $deleted['photos']  = DB::table('activity_result_photos')->whereIn('activity_result_id', $activityResultIds)->delete();
                $deleted['results'] = DB::table('activity_results')->whereIn('id', $activityResultIds)->delete();
            }

            foreach (Checklist::whereIn('id', $checklistIds)->cursor() as $cl) {
                if (method_exists($cl, 'items')) $cl->items()->delete();
                $cl->delete();
                $deleted['checklists']++;
            }

            DB::commit();
            $deleted['files'] = $deletedFiles;

            return response()->json([
                'success' => true,
                'message' => 'Seluruh data & file foto terkait berhasil dihapus.',
                'deleted' => $deleted,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Gagal menghapus massal: '.$e->getMessage()], 500);
        }
    }
}
