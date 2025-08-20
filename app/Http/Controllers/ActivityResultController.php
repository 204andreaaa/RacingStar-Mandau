<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityResultController extends Controller
{
    public function bulkStore(Request $req, \App\Models\Checklist $checklist)
    {
        // Ambil daftar activity_id yang ada di form
        $activityIds = array_map('intval', array_keys($req->input('status', [])));
        if (empty($activityIds)) {
            return back()->with('success', 'Tidak ada perubahan.');
        }

        // Loop tiap activity; hanya proses yang statusnya "done" atau "skipped" (checkbox -> "on" = done)
        foreach ($activityIds as $aid) {
            $status = $req->input("status.$aid") === 'on' ? 'done' : 'skipped';

            $activity = Activity::find($aid);
            if (!$activity) { continue; }

            // Ambil record lama jika ada; kalau tidak ada, buat baru (belum disimpan)
            $record = ActivityResult::firstOrNew([
                'checklist_id' => $checklist->id,
                'activity_id'  => $aid,
            ]);

            // VALIDASI foto:
            // - kalau status DONE dan BELUM ada foto lama → required
            // - kalau status DONE tetapi SUDAH ada foto lama → nullable (boleh kosong, tidak wajib reupload)
            if ($status === 'done') {
                $rules = [
                    "before_photo.$aid" => [empty($record->before_photo) ? 'required' : 'nullable', 'image','mimes:jpg,jpeg,png','max:5120'],
                    "after_photo.$aid"  => [empty($record->after_photo)  ? 'required' : 'nullable', 'image','mimes:jpg,jpeg,png','max:5120'],
                ];
                $req->validate($rules);
            }

            // Upload bila ada file baru
            if ($req->hasFile("before_photo.$aid")) {
                $record->before_photo = $req->file("before_photo.$aid")->store('checklists/before','public');
            }
            if ($req->hasFile("after_photo.$aid")) {
                $record->after_photo = $req->file("after_photo.$aid")->store('checklists/after','public');
            }

            // Isi field lainnya
            $record->user_id      = $checklist->user_id;
            $record->submitted_at = now();
            $record->status       = $status;
            $record->point_earned = $status === 'done' ? ($activity->point ?? 0) : 0;
            $record->note         = $req->input("note.$aid");

            $record->save();
        }

        // Jika tombol "selesaikan" dipencet, sekalian close sesi
        if ($req->boolean('finish')) {
            $total = \App\Models\ActivityResult::where('checklist_id',$checklist->id)->sum('point_earned');
            $checklist->update([
                'status' => 'completed',
                'submitted_at' => now(),
                'total_point' => $total,
            ]);
            if ($checklist->user_id == 1) {
                return redirect()->route('admin.checklists.show', $checklist)->with('success', 'Checklist selesai.');
            } else {
                return redirect()->route('checklists.show_result', $checklist)->with('success', 'Checklist selesai.');
            }
        }

        return back()->with('success', 'Data checklist tersimpan.');
    }

    public function create()
    {
        // tampilkan daftar aktivitas aktif
        $activities = Activity::where('is_active', true)->orderBy('name')->get();
        return view('checklist.create', compact('activities'));
    }

    public function store(Request $req, \App\Models\Checklist $checklist)
    {
        $data = $req->validate([
            'activity_id'  => ['required','exists:activities,id'],
            'status'       => ['required','in:done,skipped'],
            'before_photo' => ['required','image','mimes:jpg,jpeg,png','max:5242880'],
            'after_photo'  => ['required','image','mimes:jpg,jpeg,png','max:5242880'],
            'note'         => ['nullable','string'],
        ]);

        $activity = \App\Models\Activity::findOrFail($data['activity_id']);
        $before = $req->file('before_photo')->store('checklists/before','public');
        $after  = $req->file('after_photo')->store('checklists/after','public');

        \App\Models\ActivityResult::create([
            'checklist_id' => $checklist->id,
            'user_id'      => $checklist->user_id,
            'activity_id'  => $activity->id,
            'submitted_at' => now(),
            'status'       => $data['status'],
            'before_photo' => $before,
            'after_photo'  => $after,
            'point_earned' => $data['status'] === 'done' ? $activity->point : 0,
            'note'         => $data['note'] ?? null,
        ]);

        return back()->with('success','Aktivitas ditambahkan ke ceklis.');
    }

    public function summary(Request $request)
    {
        $userId = $request->query('user_id', auth()->id() ?? 1);
        $total  = ActivityResult::where('user_id', $userId)->sum('point_earned');
        $items  = ActivityResult::with('activity')->where('user_id', $userId)
                    ->orderByDesc('submitted_at')->limit(20)->get();

        return view('checklist.summary', compact('total','items'));
    }
}
