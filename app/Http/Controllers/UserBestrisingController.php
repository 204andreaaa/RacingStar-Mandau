<?php

namespace App\Http\Controllers;

use App\Models\{UserBestrising, KategoriUser, Region, Segmen};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserBestrisingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = UserBestrising::with(['kategoriUser','segmens'])->select('user_bestrising.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('kategori', fn($row) => $row->kategoriUser->nama_kategoriuser ?? '-')
                ->addColumn('action', function ($row) {
                    $segmenIds = $row->segmens->pluck('id_segmen')->values()->all();
                    $segmenIdsJson = e(json_encode($segmenIds));
                    return '
                        <button class="btn btn-warning btn-sm btn-edit"
                            data-id="'.$row->id_userBestrising.'"
                            data-nik="'.$row->nik.'"
                            data-nama="'.$row->nama.'"
                            data-email="'.$row->email.'"
                            data-kategori_id="'.$row->kategori_user_id.'"
                            data-region="'.($row->id_region ?? '').'"
                            data-serpo="'.($row->id_serpo ?? '').'"
                            data-segmen=\''.$segmenIdsJson.'\'>Edit</button>
                        <button class="btn btn-danger btn-sm btn-delete"
                            data-id="'.$row->id_userBestrising.'">Hapus</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('bestRising.admin.userBestrising.index', [
            'kategoriUsers' => KategoriUser::orderBy('nama_kategoriuser')->get(),
            'regions'       => Region::orderBy('nama_region')->get(),
        ]);
    }

    /** Helper untuk baca nama kategori dan normalkan */
    private function kategoriNameUpper($kategori_user_id): string
    {
        $kat = KategoriUser::findOrFail($kategori_user_id);
        return mb_strtoupper(trim($kat->nama_kategoriuser ?? ''), 'UTF-8');
    }

    /** Validasi kondisional sesuai kategori */
    private function validateByCategory(Request $request, ?int $ignoreId = null): array
    {
        $base = [
            'nik'              => ['required','max:50','unique:user_bestrising,nik'.($ignoreId ? ','.$ignoreId.',id_userBestrising' : '')],
            'nama'             => ['required','string','max:255'],
            'email'            => ['required','email','unique:user_bestrising,email'.($ignoreId ? ','.$ignoreId.',id_userBestrising' : '')],
            'kategori_user_id' => ['required','exists:kategoriuser,id_kategoriuser'],
        ];

        // password rules
        if ($ignoreId) {
            $base['password'] = ['nullable','string','min:6','confirmed'];
        } else {
            $base['password'] = ['required','string','min:6','confirmed'];
        }

        $katName = $this->kategoriNameUpper($request->input('kategori_user_id'));

        if (str_contains($katName, 'ADMIN')) {
            // tidak butuh region/serpo/segmen
        } elseif (str_contains($katName, 'SERPO')) {
            $base['id_region']   = ['required','integer'];
            $base['id_serpo']    = ['required','integer'];
            $base['id_segmen']   = ['required','array','min:1'];
            $base['id_segmen.*'] = ['integer','exists:segmens,id_segmen'];
        } elseif (str_contains($katName, 'NOC')) {
            $base['id_region'] = ['required','integer'];
            // serpo & segmen tidak diwajibkan
        } else {
            // kategori lain (kalau ada) → default kosong (boleh lu sesuaikan)
        }

        return $request->validate($base);
    }

    public function store(Request $request)
    {
        $data = $this->validateByCategory($request, null);
        $katName = $this->kategoriNameUpper($data['kategori_user_id']);

        // Normalisasi field sesuai kategori
        if (str_contains($katName, 'ADMIN')) {
            $data['id_region'] = null;
            $data['id_serpo']  = null;
        } elseif (str_contains($katName, 'NOC')) {
            // hanya region; serpo null
            $data['id_serpo']  = null;
        }
        // untuk SERPO: id_region & id_serpo tetap ada

        $user = UserBestrising::create([
            'nik'              => $data['nik'],
            'nama'             => $data['nama'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'kategori_user_id' => $data['kategori_user_id'],
            'id_region'        => $data['id_region'] ?? null,
            'id_serpo'         => $data['id_serpo']  ?? null,
        ]);

        // Handle segmen
        if (str_contains($katName, 'SERPO')) {
            $user->segmens()->sync($request->input('id_segmen', []));
        } else {
            $user->segmens()->sync([]); // kosongkan bila bukan kategori SERPO
        }

        return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateByCategory($request, (int)$id);
        $katName = $this->kategoriNameUpper($data['kategori_user_id']);

        $user = UserBestrising::findOrFail($id);

        $payload = [
            'nik'              => $data['nik'],
            'nama'             => $data['nama'],
            'email'            => $data['email'],
            'kategori_user_id' => $data['kategori_user_id'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if (str_contains($katName, 'ADMIN')) {
            $payload['id_region'] = null;
            $payload['id_serpo']  = null;
        } elseif (str_contains($katName, 'SERPO')) {
            $payload['id_region'] = $data['id_region'] ?? null;
            $payload['id_serpo']  = $data['id_serpo']  ?? null;
        } elseif (str_contains($katName, 'NOC')) {
            $payload['id_region'] = $data['id_region'] ?? null;
            $payload['id_serpo']  = null; // pastikan null
        }

        $user->update($payload);

        // Sync segmen sesuai kategori
        if (str_contains($katName, 'SERPO')) {
            $user->segmens()->sync($request->input('id_segmen', []));
        } else {
            $user->segmens()->sync([]); // kosongkan utk ADMIN/NOC
        }

        return response()->json(['success' => true, 'message' => 'User berhasil diupdate']);
    }

    public function destroy($id)
    {
        $user = UserBestrising::findOrFail($id);
        $user->segmens()->sync([]); // bersihkan pivot biar rapi
        $user->delete();

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus']);
    }
}
