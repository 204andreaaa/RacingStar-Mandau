<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResult;
use App\Models\ActivityResultPhoto;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ActivityResultController extends Controller
{
    /* =========================
     * Helpers: periode & kuota
     * ========================= */
    private function periodRange(string $period): array
    {
        switch ($period) {
            case 'daily':
                return [now()->startOfDay(), now()->endOfDay(), 'Hari ini'];
            case 'weekly':
                return [now()->startOfWeek(\Carbon\Carbon::MONDAY), now()->endOfWeek(\Carbon\Carbon::MONDAY), 'Minggu ini'];
            case 'monthly':
                return [now()->startOfMonth(), now()->endOfMonth(), 'Bulan ini'];
            default:
                return [null, null, 'Tidak Dibatasi'];
        }
    }

    /**
     * Hitung pemakaian kuota pada periode berjalan.
     * Gunakan tanggal submit terakhir (submitted_at) jika ada; jika null, fallback ke created_at.
     * $excludeChecklistId dipakai saat ENFORCE di simpan supaya current checklist boleh edit ulang.
     */
    private function quotaUsed( int $userId, int $activityId, string $period, ?int $exceptChecklistId = null ): int 
    {
        if ($period === 'none') return 0;

        [$from, $to] = $this->periodRange($period);

        // lockForUpdate → aman dari race condition submit ganda
        $q = ActivityResult::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->where('status', 'done')
            ->whereBetween('submitted_at', [$from, $to]);

        if ($exceptChecklistId) {
            $q->where('checklist_id', '<>', $exceptChecklistId);
        }

        return (int) $q->lockForUpdate()->count();
    }

    public function usageForActivities(int $userId, $activities): array
    {
        $usage = [];

        // Kelompokkan dulu per periode agar query-nya efisien
        foreach ($activities->groupBy(fn($a) => $a->limit_period ?? 'none') as $period => $acts) {
            if ($period === 'none') {
                foreach ($acts as $a) {
                    $usage[$a->id] = [
                        'used'    => 0,
                        'max'     => null,
                        'blocked' => false,
                        'label'   => 'Tidak dibatasi',
                    ];
                }
                continue;
            }

            [$from, $to, $label] = $this->periodRange($period);

            $rows = ActivityResult::select('activity_id', DB::raw('COUNT(*) as c'))
                ->where('user_id', $userId)
                ->where('status', 'done')
                ->whereBetween('submitted_at', [$from, $to])
                ->whereIn('activity_id', $acts->pluck('id'))
                ->groupBy('activity_id')
                ->pluck('c', 'activity_id'); // [activity_id => count]

            foreach ($acts as $a) {
                $used = (int) ($rows[$a->id] ?? 0);
                $max  = max(1, (int) ($a->limit_quota ?? 1));
                $usage[$a->id] = [
                    'used'    => $used,
                    'max'     => $max,
                    'blocked' => $used >= $max,
                    'label'   => $label,
                ];
            }
        }

        return $usage;
    }

    /* =========================
     * EDIT (render form user)
     * ========================= */
    // public function edit(Checklist $checklist)
    // {
    //     // Ambil activity aktif; filter team bila tersedia di checklist
    //     $activities = Activity::query()
    //         ->where('is_active', true)
    //         ->when(isset($checklist->team_id) && $checklist->team_id, fn($q) => $q->where('team_id', $checklist->team_id))
    //         ->when((!isset($checklist->team_id) || !$checklist->team_id) && isset($checklist->team) && $checklist->team, fn($q) => $q->where('team', $checklist->team))
    //         ->orderBy('name')
    //         ->get(['id','name','description','limit_period','limit_quota','point']);

    //     // Item yang sudah tersimpan pada checklist ini (+ foto), lalu keyBy activity_id
    //     $items = ActivityResult::where('checklist_id', $checklist->id)
    //         ->with(['beforePhotos','afterPhotos'])
    //         ->get()
    //         ->keyBy('activity_id');

    //     $usage = $this->usageForActivities($checklist->user_id, $activities);

    //     // Hitung usage/kuota untuk badge
    //     $usage = [];
    //     foreach ($activities as $a) {
    //         $period = $a->limit_period ?? 'none';

    //         if ($period === 'none') {
    //             $usage[$a->id] = [
    //                 'used'    => 0,
    //                 'max'     => null,
    //                 'blocked' => false,
    //                 'label'   => 'Tidak dibatasi',
    //             ];
    //             continue;
    //         }

    //         $max = max(1, (int)($a->limit_quota ?? 1));
    //         [$start, $end, $label] = $this->periodRange($period);

    //         $usedAll = ActivityResult::query()
    //             ->where('user_id', $checklist->user_id)
    //             ->where('activity_id', $a->id)
    //             ->where('status', 'done')
    //             ->whereBetween(DB::raw('COALESCE(submitted_at, created_at)'), [$start, $end])
    //             ->count();

    //         $usage[$a->id] = [
    //             'used'    => $usedAll,
    //             'max'     => $max,
    //             'blocked' => $usedAll >= $max,
    //             'label'   => $label,
    //         ];
    //     }

    //     return view('bestRising.user.ceklis.index', [
    //         'checklist'  => $checklist,
    //         'activities' => $activities,
    //         'items'      => $items,  // keyed by activity_id
    //         'usage'      => $usage,
    //     ]);
    // }

    /* =========================
     * BULK STORE (form user)
     * ========================= */
    public function store(Request $req, Checklist $checklist)
    {
        $activityIds = array_map('intval', array_keys($req->input('status', [])));
        // if (empty($activityIds)) {
        //     return back()->with('success', 'Tidak ada perubahan.');
        // }

        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($activityIds as $aid) {
                $status = $req->input("status.$aid") === 'on' ? 'done' : 'skipped';

                $activity = Activity::find($aid);
                if (!$activity) { continue; }

                // ===== ENFORCE LIMIT
                $period = $activity->limit_period ?? 'none';
                if ($status === 'done' && $period !== 'none') {
                    $quota = max(1, (int)($activity->limit_quota ?? 1));
                    // dihitung per user + activity, across semua checklist
                    $used  = $this->quotaUsed($checklist->user_id, $aid, $period, $checklist->id);
                    if ($used >= $quota) {
                        [, , $label] = $this->periodRange($period);
                        $errors[] = "Aktivitas '{$activity->name}' sudah mencapai batas {$quota}× {$label}.";
                        continue;
                    }
                }

                // Ambil / buat record
                $record = ActivityResult::firstOrNew([
                    'checklist_id' => $checklist->id,
                    'activity_id'  => $aid,
                ]);

                // Validasi segmen jika wajib
                $segmenId = $req->input("id_segmen.$aid");
                if ($activity->is_checked_segmen) {
                    $rule = ($status === 'done')
                        ? ['required','integer','exists:segmens,id_segmen']
                        : ['nullable','integer','exists:segmens,id_segmen'];
                    $req->validate(["id_segmen.$aid" => $rule]);
                }

                // Simpan record dulu supaya punya ID (kalau baru)
                $record->user_id      = $checklist->user_id;
                $record->status       = $status;
                $record->point_earned = $status === 'done' ? ($activity->point ?? 0) : 0;
                $record->note         = $req->input("note.$aid");
                $record->id_segmen    = $segmenId !== null && $segmenId !== '' ? (int)$segmenId : null;
                $record->submitted_at = now();
                $record->save(); // <-- penting, supaya $record->id ada

                // VALIDASI file untuk status DONE (opsi minimal 1 kalau belum ada sama sekali)
                if ($status === 'done') {
                    $hasBefore = $record->beforePhotos()->exists();
                    $hasAfter  = $record->afterPhotos()->exists();

                    $rules = [
                        "before_photo.$aid"   => [
                            $hasBefore ? 'nullable' : 'required',
                            'array',
                            'max:3',
                        ],
                        "before_photo.$aid.*" => ['image','mimes:jpg,jpeg,png','max:5120'],

                        "after_photo.$aid"    => [
                            $hasAfter ? 'nullable' : 'required',
                            'array',
                            'max:3',
                        ],
                        "after_photo.$aid.*"  => ['image','mimes:jpg,jpeg,png','max:5120'],
                    ];

                    $req->validate($rules);
                }

                // ==== BEFORE (replace total bila ada upload baru) ====
                if ($req->hasFile("before_photo.$aid")) {
                    // hapus set lama (row + file)
                    foreach ($record->beforePhotos as $p) {
                        if ($p->path && Storage::disk('public')->exists($p->path)) {
                            Storage::disk('public')->delete($p->path);
                        }
                    }
                    $record->beforePhotos()->delete();

                    $files = $req->file("before_photo.$aid");
                    foreach (array_slice($files, 0, 3) as $idx => $f) {
                        if (!$f || !$f->isValid()) continue;
                        $path = $f->store('checklists/before', 'public');
                        ActivityResultPhoto::create([
                            'activity_result_id' => $record->id,
                            'kind'       => 'before',
                            'path'       => $path,
                        ]);
                    }
                }

                // ==== AFTER (replace total bila ada upload baru) ====
                if ($req->hasFile("after_photo.$aid")) {
                    foreach ($record->afterPhotos as $p) {
                        if ($p->path && Storage::disk('public')->exists($p->path)) {
                            Storage::disk('public')->delete($p->path);
                        }
                    }
                    $record->afterPhotos()->delete();

                    $files = $req->file("after_photo.$aid");
                    foreach (array_slice($files, 0, 3) as $idx => $f) {
                        if (!$f || !$f->isValid()) continue;
                        $path = $f->store('checklists/after', 'public');
                        ActivityResultPhoto::create([
                            'activity_result_id' => $record->id,
                            'kind'       => 'after',
                            'path'       => $path,
                        ]);
                    }
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return back()->with('error', implode("\n", $errors));
            }

            // “Selesai & submit”
            if ($req->boolean('finish')) {
                $total = ActivityResult::where('checklist_id',$checklist->id)->sum('point_earned');
                $checklist->update([
                    'status'       => 'completed',
                    'submitted_at' => now(),
                    'total_point'  => $total,
                ]);
                DB::commit();
                return $checklist->user_id == 1
                    ? redirect()->route('admin.checklists.show', $checklist)->with('success', 'Checklist selesai.')
                    : redirect()->route('checklists.show_result', $checklist)->with('success', 'Checklist selesai.');
            }

            DB::commit();
            return back()->with('success', 'Data checklist tersimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Gagal menyimpan data. '.$e->getMessage());
        }
    }

    /* =========================
     * Create (opsional)
     * ========================= */
    public function create()
    {
        $activities = Activity::where('is_active', true)->orderBy('name')->get();
        return view('checklist.create', compact('activities'));
    }

    // /* =========================
    //  * Store single item (opsional)
    //  * ========================= */
    // public function store(Request $req, Checklist $checklist)
    // {
    //     $data = $req->validate([
    //         'activity_id'  => ['required','exists:activities,id'],
    //         'id_segmen'    => ['nullable'],
    //         'status'       => ['required','in:done,skipped'],
    //         'before_photo' => ['required','image','mimes:jpg,jpeg,png','max:5120'],
    //         'after_photo'  => ['required','image','mimes:jpg,jpeg,png','max:5120'],
    //         'note'         => ['nullable','string'],
    //     ]);

    //     $activity = Activity::findOrFail($data['activity_id']);

    //     // ENFORCE LIMIT juga di endpoint single (pakai submitted_at/created_at)
    //     $period = $activity->limit_period ?? 'none';
    //     if ($data['status'] === 'done' && $period !== 'none') {
    //         $quota = max(1, (int)($activity->limit_quota ?? 1));
    //         $used  = $this->quotaUsed($checklist->user_id, $activity->id, $period, $checklist->id);

    //         if ($used >= $quota) {
    //             [, , $label] = $this->periodRange($period);
    //             return back()->with('error', "Aktivitas '{$activity->name}' sudah mencapai batas {$quota}× {$label}.");
    //         }
    //     }

    //     $before = $req->file('before_photo')->store('checklists/before','public');
    //     $after  = $req->file('after_photo')->store('checklists/after','public');

    //     ActivityResult::create([
    //         'checklist_id' => $checklist->id,
    //         'user_id'      => $checklist->user_id,
    //         'activity_id'  => $activity->id,
    //         'submitted_at' => now(),
    //         'status'       => $data['status'],
    //         'before_photo' => $before,
    //         'after_photo'  => $after,
    //         'point_earned' => $data['status'] === 'done' ? ($activity->point ?? 0) : 0,
    //         'note'         => $data['note'] ?? null,
    //     ]);

    //     return back()->with('success','Aktivitas ditambahkan ke ceklis.');
    // }

    /* =========================
     * Summary (tetap)
     * ========================= */
    public function summary(Request $request)
    {
        $userId = $request->query('user_id', auth()->id() ?? 1);
        $total  = ActivityResult::where('user_id', $userId)->sum('point_earned');
        $items  = ActivityResult::with('activity')->where('user_id', $userId)
                    ->orderByDesc('submitted_at')->limit(20)->get();

        return view('checklist.summary', compact('total','items'));
    }
}
