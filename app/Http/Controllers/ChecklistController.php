<?php
// app/Http/Controllers/ChecklistController.php
namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\Activity;
use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;
use App\Models\ActivityResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ChecklistController extends Controller
{
public function start(Request $req)
    {
        $team = strtoupper($req->query('team', 'SERPO'));

        $users = DB::table('user_bestrising')->select('id_userBestrising as id','nama')->orderBy('nama')->get();
        $regions = \App\Models\Region::orderBy('nama_region')->get(['id_region','nama_region']);

        return view('bestRising.user.start', compact('team','users','regions'));
    }

  // POST create sesi
  public function store(Request $req)
  {
      // Ambil kategori dari session manual auth
      $auth = session('auth_user') ?? [];
      $kat  = strtoupper(trim($auth['kategori_nama'] ?? '')); // 'SERPO' | 'NOC' | 'ADMIN' | dll

      // RULES dasar (selalu wajib)
      $rules = [
          'team'      => ['required','string'],
          'user_id'   => ['required','integer'],
          'id_region' => ['required','integer'],
      ];

      // Kalau SERPO, wajib serpo + segmen
      if ($kat === 'SERPO') {
          $rules['id_serpo']  = ['required','integer'];
      }

      // NOC/ADMIN: serpo & segmen tidak diwajibkan
      $data = $req->validate($rules);

      // Normalisasi field yg tidak dipakai
      $idSerpo  = $kat === 'SERPO' ? ($data['id_serpo']  ?? null) : null;

      $checklist = Checklist::create([
          'user_id'   => $data['user_id'],
          'team'      => $data['team'],
          'id_region' => $data['id_region'],
          'id_serpo'  => $idSerpo,   // null utk NOC/ADMIN
          'started_at'=> now(),
          'status'    => 'pending',
      ]);

      // kalau route model binding belum diset, pakai id
      return redirect()->route('checklists.show', $checklist->id);
  }


  // Halaman ceklis: list aktivitas + form upload item + list item yang sudah ditambahkan
  public function show(Checklist $checklist)
  {
      // ambil team dari session:kategori_nama (case-insensitive)
      $kat = strtolower((string) data_get(session('auth_user'), 'kategori_nama', ''));
      $teamId = str_contains($kat, 'serpo') ? 1 : (str_contains($kat, 'noc') ? 2 : null);

      $activities = Activity::query()
          ->where('is_active', true)
          ->when($teamId, fn($q) => $q->where('team_id', $teamId))
          ->orderBy('name')
          ->get();

      // items yang sudah disimpan (filter berdasar team activity kalau team terdeteksi)
      $items = ActivityResult::with('activity')
          ->where('checklist_id', $checklist->id)
          ->when($teamId, fn($q) => $q->whereHas('activity', fn($qa) => $qa->where('team_id', $teamId)))
          ->orderByDesc('submitted_at')
          ->get();

      return view('bestRising.user.ceklis.index', compact('checklist','activities','items'));
  }


  public function show_result(Checklist $checklist)
  {
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

    return view('bestRising.user.ceklis.show', compact('checklist','meta','items'));
  }

  // Selesai sesi: hitung total
  public function finish(Request $req, Checklist $checklist)
  {
    $total = ActivityResult::where('checklist_id', $checklist->id)->sum('point_earned');
    $checklist->update([
      'status'       => 'completed',
      'submitted_at' => now(),
      'total_point'  => $total,
    ]);
    return back()->with('success', 'Checklist selesai. Total poin: '.$total);
  }

  public function tableCeklis(Request $request) 
  {
    
    if ($request->ajax()) {
      $currentUser = $request->session()->get('auth_user');

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
          ->where('user_id', $currentUser['id'])
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
              $edit = '<a href="'.route('checklists.show',$r->id).'" class="btn btn-sm btn-outline-warning mr-1">Edit</a>';
              $detail = '<a href="'.route('checklists.show_result',$r->id).'" class="btn btn-sm btn-outline-primary mr-1">Detail</a>';
              return $r->status == 'pending' ? $detail . $edit : $detail;
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

    return view('bestRising.user.ceklis.table-ceklis');
  }

  // API dropdown
  public function serpoByRegion($id_region)
  {
    return Serpo::where('id_region',$id_region)
      ->orderBy('nama_serpo')
      ->get(['id_serpo as id','nama_serpo as text']);
  }

  public function segmenBySerpo($id_serpo)
  {
    return Segmen::where('id_serpo',$id_serpo)
      ->orderBy('nama_segmen')
      ->get(['id_segmen as id','nama_segmen as text']);
  }

    public function segmenByRegion($id_serpo)
    {
        $regionId = Serpo::where('id_serpo', $id_serpo)->value('id_region');
        $items = Segmen::whereHas('serpo', fn($q) => $q->where('id_region', $regionId))
            ->orderBy('nama_segmen')
            ->get(['id_segmen as id','nama_segmen as text']);

        return response()->json($items);

    }

    private function currentUserId(): ?int
    {
        $sess = session('auth_user');
        return $sess['id'] ?? $sess['id_userBestrising'] ?? null;
    }

    // --- LANJUTKAN (EDIT) ---
    public function edit(Checklist $checklist)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403);
        abort_if($checklist->user_id !== $uid, 403);
        if ($checklist->status !== 'pending') {
            return redirect()->route('checklists.show', $checklist->id)
                ->with('error','Checklist sudah completed dan tidak bisa dilanjutkan.');
        }

        // tentukan teamId seperti di show()
        $kat = strtolower((string) data_get(session('auth_user'), 'kategori_nama', ''));
        $teamId = str_contains($kat, 'serpo') ? 1 : (str_contains($kat, 'noc') ? 2 : null);

        $activities = Activity::query()
            ->where('is_active', true)
            ->when($teamId, fn($q) => $q->where('team_id', $teamId))
            ->orderBy('name')->get();

        $meta = DB::table('checklists as c')
            ->leftJoin('regions as r','r.id_region','=','c.id_region')
            ->leftJoin('serpos  as s','s.id_serpo','=','c.id_serpo')
            ->leftJoin('segmens as g','g.id_segmen','=','c.id_segmen')
            ->where('c.id', $checklist->id)
            ->select(['c.*','r.nama_region','s.nama_serpo','g.nama_segmen'])
            ->first();

        // hasil existing dipetakan per activity_id
        $resultsByAid = ActivityResult::where('checklist_id',$checklist->id)
            ->get()->keyBy('activity_id');

        return view('bestRising.user.ceklis.edit',
            compact('checklist','meta','activities','resultsByAid'));
    }

    // --- UPDATE (save draft / complete) ---
    public function update(Request $request, Checklist $checklist)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403);
        abort_if($checklist->user_id !== $uid, 403);
        if ($checklist->status !== 'pending') {
            return redirect()->route('checklists.show',$checklist->id)
                ->with('error','Checklist sudah completed dan tidak bisa diupdate.');
        }

        $request->validate([
            'point_earned.*' => 'nullable|integer|min:0',
            'note.*'         => 'nullable|string|max:2000',
            'before_photo.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'after_photo.*'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'submit_action'  => 'required|in:save,complete',
        ]);

        // kumpulkan semua activity_id yang tersentuh
        $ids = collect([
            array_keys($request->input('status',[])),
            array_keys($request->input('point_earned',[])),
            array_keys($request->input('note',[])),
            array_keys($request->file('before_photo',[]) ?: []),
            array_keys($request->file('after_photo',[])  ?: []),
        ])->flatten()->unique()->map(fn($v)=>(int)$v)->filter()->values();

        foreach ($ids as $aid) {
            $activity = Activity::find($aid);
            if (!$activity) continue;

            $res = ActivityResult::firstOrNew([
                'checklist_id' => $checklist->id,
                'activity_id'  => $aid,
            ]);

            $isDone = (bool) $request->input("status.$aid");
            $res->user_id      = $checklist->user_id;
            $res->submitted_at = $res->submitted_at ?: now();
            $res->status       = $isDone ? 'done' : 'skipped';

            // ⬇️ fallback ke activity->point kalau input kosong dan status done
            $incoming = $request->input("point_earned.$aid");
            $fallback = $res->point_earned ?? ($activity->point ?? 0);
            $res->point_earned = $isDone ? (int) ($incoming === null ? $fallback : $incoming) : 0;

            $res->note = $request->input("note.$aid", $res->note);

            if ($request->hasFile("before_photo.$aid")) {
                if ($res->before_photo) Storage::disk('public')->delete($res->before_photo);
                $res->before_photo = $request->file("before_photo.$aid")
                    ->store("activity_results/{$checklist->id}/$aid", 'public');
            }
            if ($request->hasFile("after_photo.$aid")) {
                if ($res->after_photo) Storage::disk('public')->delete($res->after_photo);
                $res->after_photo = $request->file("after_photo.$aid")
                    ->store("activity_results/{$checklist->id}/$aid", 'public');
            }

            $res->save();
        }

        // recalc total & optionally complete
        $checklist->total_point = ActivityResult::where('checklist_id', $checklist->id)->sum('point_earned');
        if ($request->input('submit_action') === 'complete') {
            $checklist->status = 'completed';
            $checklist->submitted_at = now();
        }
        $checklist->save();

        return redirect()->route('checklists.show', $checklist->id)
            ->with('success', $request->input('submit_action') === 'complete'
                ? 'Checklist diselesaikan.'
                : 'Draft checklist tersimpan.');
    }
}

