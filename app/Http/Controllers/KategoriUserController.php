<?php

namespace App\Http\Controllers;

use App\Models\KategoriUser;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KategoriUserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Pastikan nama kolom sesuai dengan DB
            $data = KategoriUser::select([
                'id_kategoriuser as id', // alias supaya DataTables punya 'id'
                'nama_kategoriuser as nama_kategori'
            ]);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-warning btn-sm btn-edit"
                                data-id="'.$row->id.'"
                                data-nama_kategori="'.$row->nama_kategori.'">Edit</button>
                            <button class="btn btn-danger btn-sm btn-delete"
                                data-id="'.$row->id.'">Hapus</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        // sesuai struktur folder view
        return view('bestRising.admin.kategoriUser.index', [
            'type_menu' => 'kategoriUser'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255',
        ]);

        $kategori = KategoriUser::create([
            'nama_kategoriuser' => $request->nama_kategori
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil ditambahkan!',
            'data' => $kategori
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255',
        ]);

        $kategori = KategoriUser::findOrFail($id);
        $kategori->update([
            'nama_kategoriuser' => $request->nama_kategori
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui!',
            'data' => $kategori
        ]);
    }

    public function destroy($id)
    {
        $kategori = KategoriUser::findOrFail($id);
        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus!'
        ]);
    }
}
