<?php

namespace App\Http\Controllers;

use App\Exports\AllResultsExport;
use App\Exports\ChecklistsExport;
use App\Models\{Checklist, ActivityResult};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AdminChecklistController extends Controller
{
    public function totalStar(Request $req)
    {
        $sessionRegionId = $this->sessionRegionId();

        $region = $sessionRegionId ?? ($req->filled('region') ? (int)$req->region : null);
        $serpo  = $req->filled('serpo') ? (int)$req->serpo : null;
        $from   = $req->input('date_from');
        $to     = $req->input('date_to');

        $q = DB::table('activity_results as ar')
            ->join('user_bestrising as u', 'u.id_userBestrising', '=', 'ar.user_id')
            ->where('ar.is_approval', true)
            ->whereNull('ar.deleted_at');

        if (!is_null($region)) $q->where('u.id_region', $region);
        if (!is_null($serpo))  $q->where('u.id_serpo',  $serpo);

        // pakai submitted_at biar konsisten dengan allresult()
        if ($from || $to) {
            if ($from && $to)       $q->whereBetween(DB::raw('DATE(ar.submitted_at)'), [$from, $to]);
            elseif ($from)          $q->whereDate('ar.submitted_at', '>=', $from);
            elseif ($to)            $q->whereDate('ar.submitted_at', '<=', $to);
        }

        $total = (int) ($q->sum(DB::raw('COALESCE(ar.point_earned,0)')) ?? 0);

        return response()->json(['total_star' => $total]);
    }
    
    // Ambil ID region dari session auth_user (kalau ada).
    private function sessionRegionId(): ?int
    {
        // Sesuaikan key jika beda: 'id_region' di session('auth_user')
        $rid = data_get(session('auth_user'), 'id_region');
        if (is_null($rid) || $rid === '') return null;
        return (int) $rid;
    }

    public function allresult(Request $request)
    {
        // Ambil region dari session → untuk scoping
        $sessionRegionId = $this->sessionRegionId();

        if ($request->ajax()) {
            // Subquery foto
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
                    DB::raw('REPLACE(REPLACE(ar.sub_activities, "&quot;", ""), \'"\', "") as sub_activity_nama'), 
                    DB::raw('COALESCE(u.nama, u.email) as user_nama'),
                    DB::raw('act.name as activity_nama'),
                    DB::raw('COALESCE(r.nama_region, "-") as region_nama'),
                    DB::raw('COALESCE(sp.nama_serpo, "-") as serpo_nama'),
                    DB::raw('COALESCE(sg.nama_segmen, "-") as segmen_nama'),
                    DB::raw('sg.id_segmen as segmen_id'),
                    DB::raw('bfl.before_list'),
                    DB::raw('afl.after_list'),
                ])
                ->where('is_approval', true);

            // === ENFORCE REGION BY SESSION ===
            if (!is_null($sessionRegionId)) {
                $q->where('u.id_region', $sessionRegionId);
            }

            // === FILTERS (request) ===
            // Region: hanya dipakai kalau sessionRegionId null
            if (is_null($sessionRegionId) && $request->filled('region')) {
                $q->where('u.id_region', (int) $request->region);
            }
            if ($request->filled('serpo'))  $q->where('u.id_serpo',  (int) $request->serpo);

            // Segmen
            if ($request->filled('segmen')) {
                $segmenVal = (int) $request->segmen;
                if ($segmenVal === 0) {
                    $q->whereNull('ar.id_segmen');
                } else {
                    $q->where('ar.id_segmen', $segmenVal);
                }
            }

            // Tanggal
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $from = $request->date_from; $to = $request->date_to;
                if ($from && $to) $q->whereBetween(DB::raw('DATE(ar.submitted_at)'), [$from, $to]);
                elseif ($from)   $q->whereDate('ar.submitted_at', '>=', $from);
                elseif ($to)     $q->whereDate('ar.submitted_at', '<=', $to);
            }

            // Keyword
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
                ->filterColumn('sub_activity_nama', fn($query,$k) => $query->where('ar.sub_activities','like','%'.$k.'%'))
                ->filterColumn('region_nama', fn($query,$k) => $query->where('r.nama_region','like','%'.$k.'%'))
                ->filterColumn('serpo_nama',  fn($query,$k) => $query->where('sp.nama_serpo','like','%'.$k.'%'))
                ->editColumn('created_at', fn($r) => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i:s') : '-')
                ->addColumn('before_photos', function ($r) {
                    if (!$r->before_list) return '-';
                    $items = [];
                    foreach (explode('|', $r->before_list) as $path) {
                        $path = trim($path ?? '');
                        if ($path === '') continue;
                        $url = asset('storage/'.ltrim($path,'/'));
                        $items[] =
                            '<div class="photo-item" title="Before">'.
                            '<img src="'.$url.'" data-full="'.$url.'" class="thumb-img" alt="before">'.
                            '<span class="photo-badge">B</span>'.
                            '</div>';
                    }
                    return '<div class="photo-scroller">'.implode('', $items).'</div>';
                })
                ->addColumn('after_photos', function ($r) {
                    if (!$r->after_list) return '-';
                    $items = [];
                    foreach (explode('|', $r->after_list) as $path) {
                        $path = trim($path ?? '');
                        if ($path === '') continue;
                        $url = asset('storage/'.ltrim($path,'/'));
                        $items[] =
                            '<div class="photo-item" title="After">'.
                            '<img src="'.$url.'" data-full="'.$url.'" class="thumb-img" alt="after">'.
                            '<span class="photo-badge">A</span>'.
                            '</div>';
                    }
                    return '<div class="photo-scroller">'.implode('', $items).'</div>';
                })
                ->rawColumns(['before_photos','after_photos'])
                ->make(true);
        }

        // Kirim sessionRegionId ke view buat preselect & lock filter Region
        return view('bestRising.admin.checklists.allresult', [
            'sessionRegionId' => $sessionRegionId,
        ]);
    }

    public function exportAllResult(Request $request)
    {
        // Pastikan export juga respect scoping region session
        $sessionRegionId = $this->sessionRegionId();

        // Gabung filter request, lalu override region jika sessionRegionId ada
        $filters = $request->only(['region','serpo','segmen','date_from','date_to','keyword']);
        if (!is_null($sessionRegionId)) {
            $filters['region'] = $sessionRegionId; // paksa region sesuai session
        }

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
                ->when($request->date_to,   fn($qq) => $qq->whereDate('started_at', '<=', $request->date_to));

            // robust region filter (tidak diubah -> bukan fokus halaman ini)
            if ($request->filled('region')) {
                $region = (int) $request->region;
                if (\Schema::hasColumn('checklists', 'region_id')) {
                    $q->where('region_id', $region);
                } elseif (\Schema::hasColumn('checklists', 'id_region')) {
                    $q->where('id_region', $region);
                } else {
                    $q->whereHas('user', fn($u) => $u->where('id_region', $region));
                }
            }

            // robust serpo filter
            if ($request->filled('serpo')) {
                $serpo = (int) $request->serpo;
                if (\Schema::hasColumn('checklists', 'serpo_id')) {
                    $q->where('serpo_id', $serpo);
                } elseif (\Schema::hasColumn('checklists', 'id_serpo')) {
                    $q->where('id_serpo', $serpo);
                } else {
                    $q->whereHas('user', fn($u) => $u->where('id_serpo', $serpo));
                }
            }

            $q->orderByDesc('started_at');

            return DataTables::of($q)
                ->addIndexColumn()
                ->editColumn('started_at',   fn($row) => optional($row->started_at)->format('Y-m-d H:i:s'))
                ->editColumn('submitted_at', fn($row) => optional($row->submitted_at)->format('Y-m-d H:i:s'))
                ->addColumn('user_nama',     fn($row) => $row->user->nama ?? $row->user->email ?? '-')
                ->addColumn('lokasi', function($row){
                    $region = $row->region->nama_region ?? ($row->user->region->nama_region ?? '-');
                    $serpo  = $row->serpo->nama_serpo   ?? ($row->user->serpo->nama_serpo   ?? '-');
                    return "{$region} / {$serpo}";
                })
                ->addColumn('cb', function($row){
                    // tampilkan checkbox hanya jika BELUM completed
                    if ($row->status === 'completed' || $row->status === 'rejected') return '';
                    return '<input type="checkbox" class="cb-approve" value="'.$row->id.'">';
                })
                ->addColumn('action', function($row) {
                    $btnShow = '<a class="btn btn-sm btn-outline-primary mr-1" href="' . route('admin.checklists.show', $row->id) . '">Detail</a>';
                    
                    // hanya tampilkan tombol hapus kalau status-nya bukan 'completed'
                    $btnDel = '';
                    if ($row->status !== 'completed') {
                        $btnDel = '<button class="btn btn-sm btn-outline-danger btn-del" data-url="' . route('admin.checklists.destroy', $row->id) . '">Hapus</button>';
                    }

                    return $btnShow . ' ' . $btnDel;
                })
                ->rawColumns(['action','cb'])
                ->make(true);

            
        }

        return view('bestRising.admin.checklists.index');
    }

    public function show($id)
    {
        $checklist = Checklist::with(['user','region','serpo','segmen','items','itemsTrashed'])->findOrFail($id);
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

        $photoPaths = [];
        if (!empty($activityResultIds)) {
            $photoPaths = DB::table('activity_result_photos')
                ->whereIn('activity_result_id', $activityResultIds)
                ->pluck('path')
                ->filter()
                ->map(fn($p) => ltrim($p,'/'))
                ->all();

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

    /** Export Excel (daftar checklist) – tidak diubah */
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
     * HAPUS MASSAL sesuai filter (tidak diubah logikanya di sini).
     * Kalau mau ikut enforce region session, tinggal tambahkan seperti di allresult().
     */
    public function destroyAll(Request $request)
    {
        abort_unless((session('auth_user')['email'] ?? null) === 'superadmin@mandau.id', 403);

        $team     = $request->input('team');
        $status   = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $search   = $request->input('search');

        $regionId = $request->filled('region') ? (int) $request->input('region') : null;
        $serpoId  = $request->filled('serpo')  ? (int) $request->input('serpo')  : null;

        $checklistQuery = Checklist::query()
            ->when($team,     fn($q) => $q->where('team', $team))
            ->when($status,   fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('started_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('started_at', '<=', $dateTo));

        if ($regionId) {
            if (Schema::hasColumn('checklists', 'region_id')) {
                $checklistQuery->where('region_id', $regionId);
            } elseif (Schema::hasColumn('checklists', 'id_region')) {
                $checklistQuery->where('id_region', $regionId);
            } else {
                $checklistQuery->whereHas('user', fn($u) => $u->where('id_region', $regionId));
            }
        }

        if ($serpoId) {
            if (Schema::hasColumn('checklists', 'serpo_id')) {
                $checklistQuery->where('serpo_id', $serpoId);
            } elseif (Schema::hasColumn('checklists', 'id_serpo')) {
                $checklistQuery->where('id_serpo', $serpoId);
            } else {
                $checklistQuery->whereHas('user', fn($u) => $u->where('id_serpo', $serpoId));
            }
        }

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
                'message' => 'Seluruh data & file foto terkait sesuai filter berhasil dihapus.',
                'deleted' => $deleted,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Gagal menghapus massal: '.$e->getMessage()], 500);
        }
    }

    public function reviewChecklist(Request $request, Checklist $checklist)
    {
        $action = $request->input('action');
        $alasan = trim($request->input('alasan_tolak', ''));

        try {
            DB::transaction(function () use ($action, $alasan, $checklist) {
                if ($action === 'approve') {
                    ActivityResult::withTrashed()
                        ->where('checklist_id', $checklist->id)
                        ->update([
                            'is_approval'  => true,
                            'is_rejected'  => false,
                            'alasan_tolak' => null,
                            'deleted_at'   => null, // restore jika sebelumnya softdelete
                        ]);

                    $approved = ActivityResult::where('checklist_id', $checklist->id)->where('is_approval', true)->sum('point_earned');

                    $checklist->update([
                        'approved_point_total' => $approved,
                        'total_point'          => $approved,
                        'status'               => 'completed',
                    ]);
                }

                elseif ($action === 'reject') {
                    if ($alasan === '') {
                        throw new \RuntimeException('Alasan penolakan wajib diisi.');
                    }

                    // tandai rejected + softdelete semua item checklist
                    $results = ActivityResult::where('checklist_id', $checklist->id)->get();
                    foreach ($results as $res) {
                        $res->update([
                            'is_approval'  => false,
                            'alasan_tolak' => $alasan,
                            'is_rejected'  => true,
                        ]);
                        $res->delete(); // soft delete otomatis
                    }

                    $checklist->update([
                        'approved_point_total' => 0,
                        'total_point'          => 0,
                        'status'               => 'rejected',
                    ]);
                }

                else {
                    throw new \RuntimeException('Kategori tidak dikenali.');
                }
            });

            return response()->json([
                'ok'      => true,
                'message' => $action === 'approve' ? 'Checklist disetujui.' : 'Checklist ditolak.',
            ]);

        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function items(\App\Models\Checklist $checklist)
    {
        // muat relasi item + activity + segmen + foto before/after jika ada
        $checklist->load([
            'items.activity:id,name',
            'items.segmen:id_segmen,nama_segmen',
            'items.beforePhotos',  // sesuaikan nama relasinya
            'items.afterPhotos',   // sesuaikan nama relasinya
        ]);

        $toUrl = function ($p) { return $p ? (preg_match('#^https?://#',$p) ? $p : Storage::url(ltrim(preg_replace('#^(public|storage)/#','',$p),'/'))) : null; };

        $rows = $checklist->items->map(function($it) use ($toUrl){
            $beforeSet = collect($it->beforePhotos ?? [])->pluck('path')->filter()->values()->all();
            $afterSet  = collect($it->afterPhotos  ?? [])->pluck('path')->filter()->values()->all();
            if (empty($beforeSet) && !empty($it->before_photo)) $beforeSet = [$it->before_photo];
            if (empty($afterSet)  && !empty($it->after_photo))  $afterSet  = [$it->after_photo];

            return [
                'submitted_at' => optional($it->submitted_at)->format('Y-m-d H:i:s') ?? '-',
                'activity'     => $it->activity->name ?? '-',
                'sub_activities'=> $it->sub_activities ?: '-',
                'segmen'       => $it->segmen->nama_segmen ?? '-',
                'before'       => array_values(array_map($toUrl, $beforeSet)),
                'after'        => array_values(array_map($toUrl, $afterSet)),
                'note'         => $it->note ?? '-',
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function approveBulk(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer','distinct'],
        ]);

        $ids = $data['ids'];

        // Hanya approve yang belum completed
        $count = \App\Models\Checklist::query()
            ->whereIn('id', $ids)
            ->where('status', '!=', 'completed')
            ->update([
                'status'       => 'completed',
                'submitted_at' => Carbon::now(), // opsional: set waktu selesai saat approve
            ]);
        ActivityResult::whereIn('checklist_id', $ids)
            ->update([
                'is_approval'  => true,
            ]);

        return response()->json([
            'message' => "Berhasil approve {$count} checklist.",
            'approved'=> $count,
        ]);
    }
}