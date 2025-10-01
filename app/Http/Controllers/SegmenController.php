<?php

namespace App\Http\Controllers;

use App\Models\Segmen;
use App\Models\Serpo;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SegmenExport;

class SegmenController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Segmen::with(['serpo.region'])->select('segmens.*');

            // filter opsional
            if ($request->filled('id_serpo'))   $query->where('id_serpo', $request->id_serpo);
            if ($request->filled('id_region'))  $query->whereHas('serpo', fn($q) => $q->where('id_region', $request->id_region));

            // search global
            if ($request->has('search') && !empty($request->input('search.value'))) {
                $s = $request->input('search.value');
                $query->where(function ($q) use ($s) {
                    $q->where('nama_segmen','like',"%{$s}%")
                      ->orWhereHas('serpo', fn($sp) => $sp->where('nama_serpo','like',"%{$s}%"))
                      ->orWhereHas('serpo.region', fn($rg) => $rg->where('nama_region','like',"%{$s}%"));
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('region', fn($row) => $row->serpo?->region?->nama_region ?? '-')
                ->addColumn('serpo',  fn($row) => $row->serpo?->nama_serpo ?? '-')
                ->addColumn('action', function ($row) {
                    $rid = $row->serpo?->id_region ?? '';
                    return '
                        <button class="btn btn-warning btn-sm btn-edit"
                            data-id="'.e($row->id_segmen).'"
                            data-nama="'.e($row->nama_segmen).'"
                            data-region="'.e($rid).'"
                            data-serpo="'.e($row->id_serpo).'">Edit</button>
                        <button class="btn btn-danger btn-sm btn-delete"
                            data-id="'.e($row->id_segmen).'">Hapus</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('bestRising.admin.segmen.index', [
            'regions' => Region::orderBy('nama_region')->get(),
            'serpos'  => Serpo::orderBy('nama_serpo')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $id_region = $request->query('id_region');
        $id_serpo  = $request->query('id_serpo');
        $keyword   = $request->query('q'); // dari pencarian DataTables

        $ts = now()->format('Ymd_His');
        $filename = "segmen_{$ts}.xlsx";

        return Excel::download(new SegmenExport($id_region, $id_serpo, $keyword), $filename);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_serpo'     => 'required|exists:serpos,id_serpo',
            'nama_segmen'  => [
                'required','string','max:100',
                Rule::unique('segmens','nama_segmen')->where(fn($q) => $q->where('id_serpo', $request->id_serpo)),
            ],
        ]);

        Segmen::create($request->only('id_serpo','nama_segmen'));

        return response()->json(['success' => true, 'message' => 'Segmen berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $segmen = Segmen::findOrFail($id);

        $request->validate([
            'id_serpo'     => 'required|exists:serpos,id_serpo',
            'nama_segmen'  => [
                'required','string','max:100',
                Rule::unique('segmens','nama_segmen')
                    ->where(fn($q) => $q->where('id_serpo', $request->id_serpo))
                    ->ignore($segmen->id_segmen, 'id_segmen'),
            ],
        ]);

        $segmen->update($request->only('id_serpo','nama_segmen'));

        return response()->json(['success' => true, 'message' => 'Segmen berhasil diupdate']);
    }

    public function destroy($id)
    {
        Segmen::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Segmen berhasil dihapus']);
    }

    // ==== Endpoint bantu untuk dependent dropdown SEGMENT (kalau dibutuhkan) ====
    public function bySerpo($id_serpo)
    {
        $items = Segmen::where('id_serpo', $id_serpo)
            ->orderBy('nama_segmen')
            ->get(['id_segmen as id','nama_segmen as text']);

        return response()->json($items);
    }

    // ========= BULK STORE =========
    public function bulkStore(Request $request)
    {
        $request->validate([
            'id_serpo' => 'required|exists:serpos,id_serpo',
            'names'    => 'required|string',
        ]);

        $raw = preg_split("/\r\n|\n|\r/", $request->names);
        $clean = collect($raw)
            ->map(fn($v) => preg_replace('/\s+/', ' ', trim($v)))
            ->filter(fn($v) => $v !== '')
            ->map(fn($v) => mb_strimwidth($v, 0, 100, '')) // batas 100 char
            ->unique()
            ->values();

        if ($clean->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada nama segmen yang valid.',
            ], 422);
        }

        $existing = Segmen::where('id_serpo', $request->id_serpo)
            ->whereIn('nama_segmen', $clean)
            ->pluck('nama_segmen');

        $toInsert = $clean->diff($existing)->values();

        $now = now();
        $rows = $toInsert->map(fn($name) => [
            'id_serpo'    => $request->id_serpo,
            'nama_segmen' => $name,
            'created_at'  => $now,
            'updated_at'  => $now,
        ])->all();

        DB::transaction(function() use ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                Segmen::insert($chunk);
            }
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Import segmen selesai.',
            'created'  => $toInsert->count(),
            'skipped'  => $clean->count() - $toInsert->count(),
            'total_in' => $clean->count(),
        ]);
    }
}
