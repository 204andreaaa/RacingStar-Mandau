<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ActivityController extends Controller
{
    // Kalau pakai team_id: 1=Serpo, 2=NOC
    private array $teams = [1 => 'Serpo', 2 => 'NOC'];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $teamFilter = $request->input('team'); // null/'' | 1 | 2
            $q = Activity::query()
                ->when($teamFilter !== null && $teamFilter !== '',
                    fn($qq) => $qq->where('team_id', (int) $teamFilter)
                );

            return DataTables::of($q)
                ->addIndexColumn()
                ->addColumn('team', fn($r) => $this->teams[$r->team_id] ?? $r->team_id)
                ->addColumn('status', fn($r) => $r->is_active ? 'Aktif' : 'Tidak Aktif')
                ->addColumn('is_checked_segmen', fn($r) => $r->is_checked_segmen ? 'Wajib' : 'Tidak Wajib')
                ->addColumn('action', function ($r) {
                    return '
                        <button type="button" class="btn btn-sm btn-warning btn-edit"
                            data-id="'.$r->id.'"
                            data-team="'.$r->team_id.'"
                            data-name="'.e($r->name).'"
                            data-desc="'.e($r->description ?? '').'"
                            data-point="'.$r->point.'"
                            data-active="'.($r->is_active?1:0).'"
                            data-is_checked_segmen="'.($r->is_checked_segmen?1:0).'"
                            data-limit_period="'.e($r->limit_period ?? 'none').'"
                            data-limit_quota="'.(int)($r->limit_quota ?? 1).'">Edit</button>
                        <button type="button" class="btn btn-sm btn-danger btn-delete"
                            data-id="'.$r->id.'">Hapus</button>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }


        return view('bestRising.admin.aktifitas.index', [
            'teams' => $this->teams,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'team_id'      => ['required','integer','in:1,2'],
            'name'         => ['required','string','max:255'],
            'description'  => ['nullable','string'],
            'point'        => ['required','integer','min:0'],
            'is_active'    => ['nullable'],
            'is_checked_segmen' => ['required'],
            'limit_period' => ['required','in:none,daily,weekly,monthly'],
            'limit_quota'  => ['required','integer','min:1'],
        ]);

        Activity::create($data);
        return response()->json(['message' => 'Aktivitas dibuat']);
    }

    public function update(Request $r, Activity $activity) // param harus {activity}
    {
        $data = $r->validate([
            'team_id'      => ['required','integer','in:1,2'],
            'name'         => ['required','string','max:255'],
            'description'  => ['nullable','string'],
            'point'        => ['required','integer','min:0'],
            'is_active'    => ['nullable'],
            'is_checked_segmen' => ['required'],
            'limit_period' => ['required','in:none,daily,weekly,monthly'],
            'limit_quota'  => ['required','integer','min:1'],
        ]);

        $activity->update($data);
        return response()->json(['message' => 'Aktivitas diupdate']);
    }

    // ==== FIX DELETE ====
    public function destroy(Activity $activity) // implicit binding ke {activity}
    {
        $activity->delete();
        return response()->json(['message' => 'Aktivitas dihapus']);
    }

    public function create(){ abort(404); }
    public function edit(){ abort(404); }
    public function show(){ abort(404); }
}
