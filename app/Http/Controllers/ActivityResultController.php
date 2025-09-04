<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResult;
use App\Models\ActivityResultPhoto;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image; // <-- ADD

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

    /* ======================================================
     * NEW: Simpan gambar terkompres (≈≤1MB) ke disk 'public'
     * ====================================================== */
    private function saveCompressedImage(\Illuminate\Http\UploadedFile $file, string $folder): string
    {
        $maxBytes   = 1024 * 1024; // 1MB
        $maxW       = 1920;
        $maxH       = 1920;
        $quality    = 85;  // awal
        $minQuality = 60;  // batas bawah agar tidak terlalu pecah

        // Baca dan perbaiki orientasi EXIF
        $img = Image::make($file->getRealPath())->orientate();

        // Resize kalau terlalu besar
        if ($img->width() > $maxW || $img->height() > $maxH) {
            $img->resize($maxW, $maxH, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            });
        }

        // Encode JPG & turunkan kualitas sampai kira-kira <= 1MB
        $img->encode('jpg', $quality);
        while (strlen((string) $img) > $maxBytes && $quality > $minQuality) {
            $quality -= 5;
            $img->encode('jpg', $quality);
        }

        // Jika masih di atas 1MB, kecilkan dimensi sedikit (maks 3x)
        $attempt = 0;
        while (strlen((string) $img) > $maxBytes && $attempt < 3) {
            $img->resize(
                (int) round($img->width() * 0.9),
                (int) round($img->height() * 0.9),
                function ($c) { $c->aspectRatio(); $c->upsize(); }
            )->encode('jpg', $quality);
            $attempt++;
        }

        // Simpan sebagai .jpg dengan nama unik ke folder tujuan
        $base = pathinfo($file->hashName(), PATHINFO_FILENAME) . '.jpg';
        $path = trim($folder, '/') . '/' . $base; // contoh: checklists/before/xxxx.jpg
        Storage::disk('public')->put($path, (string) $img);

        return $path;
    }

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
                        // $path = $f->store('checklists/before', 'public'); // OLD
                        $path = $this->saveCompressedImage($f, 'checklists/before'); // NEW
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
                        // $path = $f->store('checklists/after', 'public'); // OLD
                        $path = $this->saveCompressedImage($f, 'checklists/after'); // NEW
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
