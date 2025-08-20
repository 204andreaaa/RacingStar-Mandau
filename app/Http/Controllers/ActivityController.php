<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ActivityController extends Controller
{
  private array $teams = [1 => 'Serpo', 2 => 'NOC']; // mapping simpel

  public function index(Request $request)
  {
    if ($request->ajax()) {
      $teamFilter = $request->integer('team'); // kosong = semua

      return DataTables::of(
          Activity::query()
            ->when($teamFilter, fn($q) => $q->where('team_id', $teamFilter))
            ->latest('id')
        )
        ->addIndexColumn()
        ->addColumn('team', fn($r) => $this->teams[$r->team_id] ?? $r->team_id)
        ->addColumn('status', fn($r) => $r->is_active ? 'Aktif' : 'Nonaktif')
        ->addColumn('action', function($r){
          return '
            <button class="btn btn-sm btn-warning btn-edit"
              data-id="'.$r->id.'"
              data-name="'.e($r->name).'"
              data-desc="'.e($r->description ?? '').'"
              data-point="'.$r->point.'"
              data-active="'.($r->is_active?1:0).'"
              data-team="'.$r->team_id.'">Edit</button>
            <button class="btn btn-sm btn-danger btn-delete" data-id="'.$r->id.'">Hapus</button>';
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
      'team_id'     => ['required','integer','in:1,2'],
      'name'        => ['required','string','max:255'],
      'description' => ['nullable','string'],
      'point'       => ['required','integer','min:0'],
      'is_active'   => ['nullable'],
    ]);
    $data['is_active'] = $r->boolean('is_active');
    Activity::create($data);
    return response()->json(['message'=>'Aktifitas dibuat']);
  }

  public function update(Request $r, Activity $activity)
  {
    $data = $r->validate([
      'team_id'     => ['required','integer','in:1,2'],
      'name'        => ['required','string','max:255'],
      'description' => ['nullable','string'],
      'point'       => ['required','integer','min:0'],
      'is_active'   => ['nullable'],
    ]);
    $data['is_active'] = $r->boolean('is_active');
    $activity->update($data);
    return response()->json(['message'=>'Aktifitas diupdate']);
  }

  public function destroy(Activity $activity)
  {
    $activity->delete();
    return response()->json(['message'=>'Aktifitas dihapus']);
  }

  public function create(){ abort(404); }
  public function edit(Activity $activity){ abort(404); }
  public function show(Activity $activity){ abort(404); }
}
