<?php

namespace App\Http\Controllers;

use App\Models\Serpo;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SerpoExport;

class SerpoController extends Controller
{
    public function export(Request $request)
    {
        $filters = [
            'id_region' => $request->input('id_region'),
            'q'         => $request->input('q'), // search global dari DataTables
        ];

        return Excel::download(new SerpoExport($filters), 'data_serpo.xlsx');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Serpo::with('region')->select('serpos.*');

            // filter by region (opsional)
            if ($request->filled('id_region')) {
                $query->where('id_region', $request->id_region);
            }

            // search global: nama_serpo / region
            if ($request->has('search') && !empty($request->input('search.value'))) {
                $s = $request->input('search.value');
                $query->where(function ($q) use ($s) {
                    $q->where('nama_serpo', 'like', "%{$s}%")
                      ->orWhereHas('region', fn($r) => $r->where('nama_region','like',"%{$s}%"));
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('region', fn($row) => $row->region?->nama_region ?? '-')
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-warning btn-sm btn-edit"
                            data-id="'.$row->id_serpo.'"
                            data-nama="'.$row->nama_serpo.'"
                            data-region="'.$row->id_region.'">Edit</button>
                        <button class="btn btn-danger btn-sm btn-delete"
                            data-id="'.$row->id_serpo.'">Hapus</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('bestRising.admin.serpo.index', [
            'regions' => Region::orderBy('nama_region')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_region'   => 'required|exists:regions,id_region',
            'nama_serpo'  => [
                'required','string','max:100',
                // unik per region
                Rule::unique('serpos','nama_serpo')->where(fn($q) => $q->where('id_region', $request->id_region)),
            ],
        ]);

        Serpo::create($request->only('id_region','nama_serpo'));

        return response()->json(['success' => true, 'message' => 'Serpo berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $serpo = Serpo::findOrFail($id);

        $request->validate([
            'id_region'   => 'required|exists:regions,id_region',
            'nama_serpo'  => [
                'required','string','max:100',
                Rule::unique('serpos','nama_serpo')
                    ->where(fn($q) => $q->where('id_region', $request->id_region))
                    ->ignore($serpo->id_serpo, 'id_serpo'),
            ],
        ]);

        $serpo->update($request->only('id_region','nama_serpo'));

        return response()->json(['success' => true, 'message' => 'Serpo berhasil diupdate']);
    }

    public function destroy($id)
    {
        Serpo::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Serpo berhasil dihapus']);
    }

    // ==== Endpoint bantu untuk dependent dropdown ====
    public function byRegion($id_region)
    {
        $items = Serpo::where('id_region', $id_region)
            ->orderBy('nama_serpo')
            ->get(['id_serpo as id','nama_serpo as text']);

        return response()->json($items);
    }
}
