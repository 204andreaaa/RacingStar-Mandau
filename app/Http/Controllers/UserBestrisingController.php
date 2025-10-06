<?php

namespace App\Http\Controllers;

use App\Models\{UserBestrising, KategoriUser, Region, Segmen};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

    /** Helper: ambil nama kategori uppercase */
    private function kategoriNameUpper($kategori_user_id): string
    {
        $kat = KategoriUser::findOrFail($kategori_user_id);
        return mb_strtoupper(trim($kat->nama_kategoriuser ?? ''), 'UTF-8');
    }

    /** Helper: generate NIK unik format RS-YYYYMMDD-xxxx (running number harian) */
    private function generateNik(): string
    {
        $prefixDate = Carbon::now()->format('Ymd');
        $prefix = "RS-{$prefixDate}-";

        // Ambil urutan terakhir untuk hari ini
        $lastNik = UserBestrising::where('nik', 'like', $prefix.'%')
            ->orderByDesc('nik')
            ->value('nik');

        $next = 1;
        if ($lastNik) {
            // ambil 4 digit terakhir
            $num = (int) substr($lastNik, -4);
            $next = $num + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    /** Endpoint untuk minta NIK berikutnya (dipakai di form Add) */
    public function nextNik()
    {
        return response()->json(['nik' => $this->generateNik()]);
    }

    /** Validasi kondisional sesuai kategori */
    private function validateByCategory(Request $request, ?int $ignoreId = null): array
    {
        $base = [
            // Catatan: nik TIDAK diwajibkan saat create (auto-generate di server)
            'nama'             => ['required','string','max:255'],
            'email'            => ['required','email','unique:user_bestrising,email'.($ignoreId ? ','.$ignoreId.',id_userBestrising' : '')],
            'kategori_user_id' => ['required','exists:kategoriuser,id_kategoriuser'],
        ];

        // Saat UPDATE, izinkan kirim nik (readonly di UI, tapi tetap valid unik)
        if ($ignoreId) {
            $base['nik'] = ['required','max:50','unique:user_bestrising,nik,'.$ignoreId.',id_userBestrising'];
        }

        // Password rules
        if ($ignoreId) {
            $base['password'] = ['nullable','string','min:6','confirmed'];
        } else {
            $base['password'] = ['required','string','min:6','confirmed'];
        }

        $katName = $this->kategoriNameUpper($request->input('kategori_user_id'));

        if (str_contains($katName, 'ADMIN')) {
            // ADMIN sekarang wajib Region
            $base['id_region'] = ['required','integer'];
            // id_serpo tidak digunakan oleh ADMIN
        } elseif (str_contains($katName, 'SERPO')) {
            $base['id_region'] = ['required','integer'];
            $base['id_serpo']  = ['required','integer'];
            // segmen auto oleh backend → tidak divalidasi dari request
        } elseif (str_contains($katName, 'NOC')) {
            $base['id_region'] = ['required','integer'];
            // id_serpo tidak digunakan oleh NOC
        }

        return $request->validate($base);
    }

    public function store(Request $request)
    {
        $data = $this->validateByCategory($request, null);
        $katName = $this->kategoriNameUpper($data['kategori_user_id']);

        // Normalisasi field sesuai kategori
        if (str_contains($katName, 'ADMIN')) {
            // ADMIN: wajib Region, Serpo null
            $data['id_serpo']  = null;
        } elseif (str_contains($katName, 'NOC')) {
            $data['id_serpo']  = null;
        }

        // Generate NIK di server (abaikan input nik dari client bila ada)
        $nikBaru = $this->generateNik();

        $user = UserBestrising::create([
            'nik'              => $nikBaru,
            'nama'             => $data['nama'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'kategori_user_id' => $data['kategori_user_id'],
            'id_region'        => $data['id_region'] ?? null,
            'id_serpo'         => $data['id_serpo']  ?? null,
        ]);

        // AUTO SYNC semua segmen berdasarkan Serpo untuk kategori SERPO
        if (str_contains($katName, 'SERPO')) {
            $segmenIds = Segmen::where('id_serpo', $data['id_serpo'] ?? 0)->pluck('id_segmen')->all();
            $user->segmens()->sync($segmenIds); // aman walau kosong
        } else {
            $user->segmens()->sync([]); // kosongkan utk ADMIN/NOC
        }

        return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan', 'nik' => $nikBaru]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateByCategory($request, (int)$id);
        $katName = $this->kategoriNameUpper($data['kategori_user_id']);

        $user = UserBestrising::findOrFail($id);

        $payload = [
            'nik'              => $data['nik'], // tetap simpan (readonly di UI)
            'nama'             => $data['nama'],
            'email'            => $data['email'],
            'kategori_user_id' => $data['kategori_user_id'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if (str_contains($katName, 'ADMIN')) {
            // ADMIN: simpan Region, Serpo null
            $payload['id_region'] = $data['id_region'] ?? null;
            $payload['id_serpo']  = null;
        } elseif (str_contains($katName, 'SERPO')) {
            $payload['id_region'] = $data['id_region'] ?? null;
            $payload['id_serpo']  = $data['id_serpo']  ?? null;
        } elseif (str_contains($katName, 'NOC')) {
            $payload['id_region'] = $data['id_region'] ?? null;
            $payload['id_serpo']  = null;
        }

        $user->update($payload);

        // AUTO SYNC semua segmen by Serpo (SERPO saja)
        if (str_contains($katName, 'SERPO')) {
            $segmenIds = Segmen::where('id_serpo', $payload['id_serpo'] ?? 0)->pluck('id_segmen')->all();
            $user->segmens()->sync($segmenIds);
        } else {
            $user->segmens()->sync([]); // kosongkan utk ADMIN/NOC
        }

        return response()->json(['success' => true, 'message' => 'User berhasil diupdate']);
    }

    public function destroy($id)
    {
        $user = UserBestrising::findOrFail($id);
        $user->segmens()->sync([]); // bersihkan pivot
        $user->delete();

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus']);
    }
}
