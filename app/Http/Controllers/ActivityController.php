<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ActivityController extends Controller
{
    // 1=Serpo, 2=NOC
    private array $teams = [1 => 'Serpo', 2 => 'NOC'];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $teamFilter = $request->input('team'); // null/'' | 1 | 2

            $q = Activity::query()
                ->when($teamFilter !== null && $teamFilter !== '',
                    fn($qq) => $qq->where('team_id', (int) $teamFilter)
                )
                ->ordered(); // <= default urut

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
                            data-limit_quota="'.(int)($r->limit_quota ?? 1).'"
                            data-requires_photo="'.($r->requires_photo?1:0).'"
                            data-sort_order="'.(int)$r->sort_order.'"
                            data-sub_activities=\''.e(json_encode($r->sub_activities ?? [])).'\' >Edit</button>
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
        $subs = $this->normalizeSubs($r->input('sub_activities', []));

        $r->merge([
            'is_active'         => $r->boolean('is_active') ? 1 : 0,
            'is_checked_segmen' => $r->boolean('is_checked_segmen') ? 1 : 0,
            'requires_photo'    => $r->boolean('requires_photo') ? 1 : 0,
            'sub_activities'    => $subs,
        ]);

        $data = $r->validate([
            'team_id'      => ['required','integer','in:1,2'],
            'name'         => ['required','string','max:255'],
            'description'  => ['nullable','string'],
            'point'        => ['required','integer','min:0'],
            'is_active'    => ['nullable'],
            'is_checked_segmen' => ['required'],
            'limit_period' => ['required','in:none,daily,weekly,monthly'],
            'limit_quota'  => ['required','integer','min:1'],
            'requires_photo' => ['nullable','boolean'],
            'sub_activities'  => ['nullable','array'],
            'sub_activities.*'=> ['nullable','string'],
            'sort_order'      => ['nullable','integer','min:1'],
        ]);

        DB::transaction(function () use (&$data) {
            $teamId = (int)$data['team_id'];
            $pos    = (int)($data['sort_order'] ?? 0);

            $max = (int)(Activity::where('team_id', $teamId)->max('sort_order') ?? 0);
            if ($pos <= 0) {
                $data['sort_order'] = $max + 1;
            } else {
                $pos = max(1, min($pos, $max + 1));
                // geser range ke bawah: >= pos => +1
                Activity::where('team_id', $teamId)
                    ->where('sort_order', '>=', $pos)
                    ->update(['sort_order' => DB::raw('sort_order + 1')]);
                $data['sort_order'] = $pos;
            }

            Activity::create($data);
        });

        return response()->json(['message' => 'Aktivitas dibuat']);
    }

    public function update(Request $r, Activity $activity)
    {
        $subs = $this->normalizeSubs($r->input('sub_activities', []));

        $r->merge([
            'is_active'         => $r->boolean('is_active') ? 1 : 0,
            'is_checked_segmen' => $r->boolean('is_checked_segmen') ? 1 : 0,
            'requires_photo'    => $r->boolean('requires_photo') ? 1 : 0,
            'sub_activities'    => $subs,
        ]);

        $data = $r->validate([
            'team_id'      => ['required','integer','in:1,2'],
            'name'         => ['required','string','max:255'],
            'description'  => ['nullable','string'],
            'point'        => ['required','integer','min:0'],
            'is_active'    => ['nullable'],
            'is_checked_segmen' => ['required'],
            'limit_period' => ['required','in:none,daily,weekly,monthly'],
            'limit_quota'  => ['required','integer','min:1'],
            'requires_photo' => ['nullable','boolean'],
            'sub_activities'     => ['nullable','array'],
            'sub_activities.*'   => ['nullable','string'],
            'sort_order'         => ['nullable','integer','min:1'],
        ]);

        DB::transaction(function () use (&$data, $activity) {
            $oldTeam = (int)$activity->team_id;
            $oldPos  = (int)$activity->sort_order;
            $newTeam = (int)$data['team_id'];
            $newPos  = (int)($data['sort_order'] ?? 0);

            // Kalau ganti team
            if ($newTeam !== $oldTeam) {
                // tutup celah di tim lama
                Activity::where('team_id', $oldTeam)
                    ->where('sort_order', '>', $oldPos)
                    ->update(['sort_order' => DB::raw('sort_order - 1')]);

                $maxNew = (int)(Activity::where('team_id', $newTeam)->max('sort_order') ?? 0);
                if ($newPos <= 0) {
                    $newPos = $maxNew + 1;
                } else {
                    $newPos = max(1, min($newPos, $maxNew + 1));
                    Activity::where('team_id', $newTeam)
                        ->where('sort_order', '>=', $newPos)
                        ->update(['sort_order' => DB::raw('sort_order + 1')]);
                }

                $data['sort_order'] = $newPos;
                $activity->update($data);
                return;
            }

            // Team sama (reposition / atau tidak)
            $max = (int)(Activity::where('team_id', $oldTeam)->max('sort_order') ?? 0);

            if ($newPos <= 0) {
                // tidak mengubah posisi (pakai posisi lama)
                $data['sort_order'] = $oldPos;
                $activity->update($data);
                return;
            }

            $newPos = max(1, min($newPos, $max)); // saat update, range 1..max

            if ($newPos === $oldPos) {
                $data['sort_order'] = $oldPos;
                $activity->update($data);
                return;
            }

            if ($newPos < $oldPos) {
                // naik ke atas: geser [newPos .. oldPos-1] +1
                Activity::where('team_id', $oldTeam)
                    ->whereBetween('sort_order', [$newPos, $oldPos - 1])
                    ->update(['sort_order' => DB::raw('sort_order + 1')]);
            } else {
                // turun ke bawah: geser [oldPos+1 .. newPos] -1
                Activity::where('team_id', $oldTeam)
                    ->whereBetween('sort_order', [$oldPos + 1, $newPos])
                    ->update(['sort_order' => DB::raw('sort_order - 1')]);
            }

            $data['sort_order'] = $newPos;
            $activity->update($data);
        });

        return response()->json(['message' => 'Aktivitas diupdate']);
    }

    public function destroy(Activity $activity)
    {
        DB::transaction(function () use ($activity) {
            $teamId = (int)$activity->team_id;
            $pos    = (int)$activity->sort_order;

            $activity->delete();

            // Tutup celah
            Activity::where('team_id', $teamId)
                ->where('sort_order', '>', $pos)
                ->update(['sort_order' => DB::raw('sort_order - 1')]);
        });

        return response()->json(['message' => 'Aktivitas dihapus']);
    }

    private function normalizeSubs($in): array
    {
        if (is_string($in)) {
            $trim = trim($in);
            if ($trim === '') return [];
            $dec = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) $in = $dec;
            else $in = preg_split('/[\r\n,]+/', $trim);
        }
        if (!is_array($in)) $in = [];
        $in = array_map(fn($v) => is_scalar($v) ? trim((string)$v) : '', $in);
        $in = array_filter($in, fn($v) => $v !== '');
        $in = array_values(array_intersect_key($in, array_unique(array_map('mb_strtolower', $in))));
        return $in;

    }

    public function create(){ abort(404); }
    public function edit(){ abort(404); }
    public function show(){ abort(404); }
}
