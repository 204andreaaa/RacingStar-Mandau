<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResult;
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
            case 'daily':   return [now()->startOfDay(),   now()->endOfDay(),   'Hari ini'];
            case 'weekly':  return [now()->startOfWeek(),  now()->endOfWeek(),  'Minggu ini'];
            case 'monthly': return [now()->startOfMonth(), now()->endOfMonth(), 'Bulan ini'];
            default:        return [now()->startOfCentury(), now()->endOfCentury(), 'Tidak dibatasi'];
        }
    }

    /**
     * Hitung pemakaian kuota pada periode berjalan.
     * Gunakan tanggal submit terakhir (submitted_at) jika ada; jika null, fallback ke created_at.
     * $excludeChecklistId dipakai saat ENFORCE di simpan supaya current checklist boleh edit ulang.
     */
    private function quotaUsed(int $userId, int $activityId, string $period, ?int $excludeChecklistId = null): int
    {
        [$start, $end] = $this->periodRange($period);

        return ActivityResult::query()
            ->where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->where('status', 'done')
            ->whereBetween(DB::raw('COALESCE(submitted_at, created_at)'), [$start, $end])
            ->when($excludeChecklistId, function ($q) use ($excludeChecklistId) {
                $q->where(function ($qq) use ($excludeChecklistId) {
                    $qq->whereNull('checklist_id')
                       ->orWhere('checklist_id', '!=', $excludeChecklistId);
                });
            })
            ->count();
    }

    /* =========================
     * EDIT (render form user)
     * ========================= */
    public function edit(Checklist $checklist)
    {
        // Ambil activity aktif; filter team bila tersedia di checklist
        $activities = Activity::query()
            ->where('is_active', true)
            ->when(isset($checklist->team_id) && $checklist->team_id, fn($q) => $q->where('team_id', $checklist->team_id))
            ->when((!isset($checklist->team_id) || !$checklist->team_id) && isset($checklist->team) && $checklist->team, fn($q) => $q->where('team', $checklist->team))
            ->orderBy('name')
            ->get(['id','name','description','limit_period','limit_quota','point']);

        // Item yang sudah tersimpan pada checklist ini
        $items = ActivityResult::where('checklist_id', $checklist->id)->get();

        // Hitung usage/kuota untuk badge: MENGHITUNG SEMUA yang done pada periode (pakai submitted_at/created_at)
        $usage = [];
        foreach ($activities as $a) {
            $period = $a->limit_period ?? 'none';

            if ($period === 'none') {
                $usage[$a->id] = [
                    'used'    => 0,
                    'max'     => null, // tidak dibatasi
                    'blocked' => false,
                    'label'   => 'Tidak dibatasi',
                ];
                continue;
            }

            $max = max(1, (int)($a->limit_quota ?? 1));
            [$start, $end, $label] = $this->periodRange($period);

            // ⬇️ hitung SEMUA 'done' di periode berjalan (pakai tanggal submit terakhir)
            $usedAll = ActivityResult::query()
                ->where('user_id', $checklist->user_id)
                ->where('activity_id', $a->id)
                ->where('status', 'done')
                ->whereBetween(DB::raw('COALESCE(submitted_at, created_at)'), [$start, $end])
                ->count();

            $usage[$a->id] = [
                'used'    => $usedAll,          // badge jadi 1/1 kalau sudah submit minggu ini
                'max'     => $max,
                'blocked' => $usedAll >= $max,  // di Blade dipakai bareng $isDone → kalau item ini sudah done, tetap boleh edit
                'label'   => $label,
            ];
        }

        return view('bestRising.user.ceklis.index', [
            'checklist'  => $checklist,
            'activities' => $activities,
            'items'      => $items,
            'usage'      => $usage,
        ]);
    }

    /* =========================
     * BULK STORE (form user)
     * ========================= */
    public function bulkStore(Request $req, Checklist $checklist)
    {
        // Ambil daftar activity_id yang ada di form
        $activityIds = array_map('intval', array_keys($req->input('status', [])));
        if (empty($activityIds)) {
            return back()->with('success', 'Tidak ada perubahan.');
        }

        $errors = [];

        foreach ($activityIds as $aid) {
            $status = $req->input("status.$aid") === 'on' ? 'done' : 'skipped';

            $activity = Activity::find($aid);
            if (!$activity) { continue; }

            // ===== ENFORCE LIMIT (hanya jika akan "done") =====
            $period = $activity->limit_period ?? 'none';
            if ($status === 'done' && $period !== 'none') {
                $quota = max(1, (int)($activity->limit_quota ?? 1));
                // Untuk enforce, abaikan record dari checklist saat ini → tetap bisa edit yg sama
                $used  = $this->quotaUsed($checklist->user_id, $aid, $period, $checklist->id);

                if ($used >= $quota) {
                    [, , $label] = $this->periodRange($period);
                    $errors[] = "Aktivitas '{$activity->name}' sudah mencapai batas {$quota}× {$label}.";
                    continue; // lewati simpan untuk activity ini
                }
            }
            // ===== END ENFORCE LIMIT =====

            // Ambil record lama jika ada; kalau tidak ada, buat baru (belum disimpan)
            $record = ActivityResult::firstOrNew([
                'checklist_id' => $checklist->id,
                'activity_id'  => $aid,
            ]);

            // VALIDASI foto untuk status DONE:
            if ($status === 'done') {
                $rules = [
                    "before_photo.$aid" => [empty($record->before_photo) ? 'required' : 'nullable', 'image','mimes:jpg,jpeg,png','max:5120'],
                    "after_photo.$aid"  => [empty($record->after_photo)  ? 'required' : 'nullable', 'image','mimes:jpg,jpeg,png','max:5120'],
                ];
                $req->validate($rules);
            }

            // Upload bila ada file baru
            if ($req->hasFile("before_photo.$aid")) {
                if ($record->before_photo && Storage::disk('public')->exists($record->before_photo)) {
                    Storage::disk('public')->delete($record->before_photo);
                }
                $record->before_photo = $req->file("before_photo.$aid")->store('checklists/before', 'public');
            }
            if ($req->hasFile("after_photo.$aid")) {
                if ($record->after_photo && Storage::disk('public')->exists($record->after_photo)) {
                    Storage::disk('public')->delete($record->after_photo);
                }
                $record->after_photo = $req->file("after_photo.$aid")->store('checklists/after', 'public');
            }

            // ❗ Ambil id_segmen untuk activity ini (kalau ada di form)
            $segmenId = $req->input("id_segmen.$aid"); // string/number/null

            // ❗ Validasi id_segmen kalau activity mewajibkan segmen
            if ($activity->is_checked_segmen) {
                // kalau status done → wajib pilih segmen, kalau skipped → boleh kosong
                $rule = ($status === 'done') ? ['required','integer','exists:segmens,id_segmen'] : ['nullable','integer','exists:segmens,id_segmen'];
                $req->validate(["id_segmen.$aid" => $rule]);
            }

            // Isi field lainnya
            $record->user_id      = $checklist->user_id;
            $record->submitted_at = now(); // dipakai untuk hitung periode
            $record->status       = $status;
            $record->point_earned = $status === 'done' ? ($activity->point ?? 0) : 0;
            $record->note         = $req->input("note.$aid");
            $record->id_segmen    = $segmenId !== null && $segmenId !== '' ? (int)$segmenId : null;

            $record->save();
        }

        if (!empty($errors)) {
            return back()->with('error', implode("\n", $errors));
        }

        // Jika tombol "selesaikan" dipencet, sekalian close sesi
        if ($req->boolean('finish')) {
            $total = ActivityResult::where('checklist_id',$checklist->id)->sum('point_earned');
            $checklist->update([
                'status'       => 'completed',
                'submitted_at' => now(),
                'total_point'  => $total,
            ]);
            return $checklist->user_id == 1
                ? redirect()->route('admin.checklists.show', $checklist)->with('success', 'Checklist selesai.')
                : redirect()->route('checklists.show_result', $checklist)->with('success', 'Checklist selesai.');
        }

        return back()->with('success', 'Data checklist tersimpan.');
    }

    /* =========================
     * Create (opsional)
     * ========================= */
    public function create()
    {
        $activities = Activity::where('is_active', true)->orderBy('name')->get();
        return view('checklist.create', compact('activities'));
    }

    /* =========================
     * Store single item (opsional)
     * ========================= */
    public function store(Request $req, Checklist $checklist)
    {
        $data = $req->validate([
            'activity_id'  => ['required','exists:activities,id'],
            'id_segmen'    => ['nullable'],
            'status'       => ['required','in:done,skipped'],
            'before_photo' => ['required','image','mimes:jpg,jpeg,png','max:5120'],
            'after_photo'  => ['required','image','mimes:jpg,jpeg,png','max:5120'],
            'note'         => ['nullable','string'],
        ]);

        $activity = Activity::findOrFail($data['activity_id']);

        // ENFORCE LIMIT juga di endpoint single (pakai submitted_at/created_at)
        $period = $activity->limit_period ?? 'none';
        if ($data['status'] === 'done' && $period !== 'none') {
            $quota = max(1, (int)($activity->limit_quota ?? 1));
            $used  = $this->quotaUsed($checklist->user_id, $activity->id, $period, $checklist->id);

            if ($used >= $quota) {
                [, , $label] = $this->periodRange($period);
                return back()->with('error', "Aktivitas '{$activity->name}' sudah mencapai batas {$quota}× {$label}.");
            }
        }

        $before = $req->file('before_photo')->store('checklists/before','public');
        $after  = $req->file('after_photo')->store('checklists/after','public');

        ActivityResult::create([
            'checklist_id' => $checklist->id,
            'user_id'      => $checklist->user_id,
            'activity_id'  => $activity->id,
            'submitted_at' => now(),
            'status'       => $data['status'],
            'before_photo' => $before,
            'after_photo'  => $after,
            'point_earned' => $data['status'] === 'done' ? ($activity->point ?? 0) : 0,
            'note'         => $data['note'] ?? null,
        ]);

        return back()->with('success','Aktivitas ditambahkan ke ceklis.');
    }

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
