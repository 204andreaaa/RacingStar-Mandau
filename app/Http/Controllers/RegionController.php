<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\RegionExport;

class RegionController extends Controller
{
    public function export(Request $request)
    {
        $filters = [
            'q' => $request->query('q'), // global search dari DataTables
        ];

        return Excel::download(new RegionExport($filters), 'data_region.xlsx');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Region::query()->select('regions.*');

            // support pencarian global (nama_region)
            if ($request->has('search') && !empty($request->input('search.value'))) {
                $search = $request->input('search.value');
                $query->where('nama_region', 'like', "%{$search}%");
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-warning btn-sm btn-edit"
                            data-id="'.$row->id_region.'"
                            data-nama="'.$row->nama_region.'">Edit</button>
                        <button class="btn btn-danger btn-sm btn-delete"
                            data-id="'.$row->id_region.'">Hapus</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('bestRising.admin.region.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_region' => 'required|string|max:100|unique:regions,nama_region',
        ]);

        Region::create($request->only('nama_region'));

        return response()->json(['success' => true, 'message' => 'Region berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $region = Region::findOrFail($id);

        $request->validate([
            'nama_region' => [
                'required','string','max:100',
                Rule::unique('regions','nama_region')->ignore($region->id_region, 'id_region'),
            ],
        ]);

        $region->update($request->only('nama_region'));

        return response()->json(['success' => true, 'message' => 'Region berhasil diupdate']);
    }

    public function destroy($id)
    {
        Region::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Region berhasil dihapus']);
    }
}
