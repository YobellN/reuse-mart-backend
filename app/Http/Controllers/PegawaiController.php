<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Penitipan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Omaressaouaf\LaravelIdGenerator\IdGenerator;


class PegawaiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pegawai = Pegawai::with('user', 'jabatan')->get();
        return response()->json([
            'message' => 'Data Pegawai',
            'data' => $pegawai
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'nama' => 'required|string|min:3',
                'email' => 'required|email|unique:user,email',
                'password' => 'required|min:8',
                'no_telp' => 'required|regex:/^[0-9]{10,15}$/',
                'id_jabatan' => 'required|exists:jabatan,id_jabatan',
                'nip' => 'required|unique:pegawai,nip',
                'tanggal_lahir' => 'required|date',
            ], [
                'nama.required' => 'Nama tidak boleh kosong',
                'nama.min' => 'Nama minimal 3 karakter',
                'password.required' => 'Password tidak boleh kosong',
                'password.min' => 'Password minimal 8 karakter',
                'no_telp.required' => 'Nomor telepon tidak boleh kosong',
                'no_telp.regex' => 'Nomor telepon tidak valid',
                'email.required' => 'Email tidak boleh kosong',
                'email.email' => 'Email tidak valid',
                'email.unique' => 'Email sudah terdaftar',
                'id_jabatan.required' => 'Jabatan tidak boleh kosong',
                'id_jabatan.exists' => 'Jabatan tidak ditemukan',
                'nip.required' => 'NIP tidak boleh kosong',
                'nip.unique' => 'NIP sudah terdaftar',
                'tanggal_lahir.required' => 'Tanggal lahir tidak boleh kosong',
                'tanggal_lahir.date' => 'Tanggal lahir tidak valid',
            ]);

            $id_pegawai = IdGenerator::generate(Pegawai::class, 'id_pegawai', 4, 'P');
            $role_pegawai = Jabatan::where('id_jabatan', $request->id_jabatan)->value('nama_jabatan');

            $user = User::create([
                'nama' => $request->nama,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'no_telp' => $request->no_telp,
                'role' => $role_pegawai,
            ]);

            $pegawai = Pegawai::create([
                'id_pegawai' => $id_pegawai,
                'id_user' => $user->id_user,
                'id_jabatan' => $request->id_jabatan,
                'nip' => $request->nip,
                'tanggal_lahir' => $request->tanggal_lahir,
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 201,
                'message' => 'Pegawai berhasil ditambahkan',
                'data' => ['user' => $user, 'pegawai' => $pegawai,],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pegawai = Pegawai::with('user', 'jabatan')->find($id);

        if (!$pegawai) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
                'errors' => ['id' => 'Pegawai tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Pegawai',
            'data' => $pegawai
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $pegawai = Pegawai::find($id);
            if (!$pegawai) {
                return response()->json([
                    'message' => 'Pegawai tidak ditemukan',
                    'errors' => ['id' => 'Pegawai tidak ditemukan'],
                ]);
            }

            $user = $pegawai->user;

            $request->validate([
                'nama' => 'sometimes|string|min:3',
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('user', 'email')->ignore($user->id_user, 'id_user')
                ],
                'password' => 'sometimes|min:8',
                'no_telp' => 'sometimes|regex:/^[0-9]{10,15}$/',
                'id_jabatan' => 'sometimes|exists:jabatan,id_jabatan',
                'nip' => [
                    'sometimes',
                    Rule::unique('pegawai', 'nip')->ignore($pegawai->nip, 'nip')
                ],
                'tanggal_lahir' => 'sometimes|date',

            ], [
                'nama.min' => 'Nama minimal 3 karakter',
                'password.min' => 'Password minimal 8 karakter',
                'email.email' => 'Email tidak valid',
                'email.unique' => 'Email sudah terdaftar',
                'no_telp.regex' => 'Nomor telepon tidak valid',
                'id_jabatan.exists' => 'Jabatan tidak ditemukan',
                'nip.unique' => 'NIP sudah terdaftar',
                'tanggal_lahir.date' => 'Tanggal lahir tidak valid',
            ]);

            $user->nama = $request->nama ?? $user->nama;
            $user->email = $request->email ?? $user->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->no_telp = $request->no_telp ?? $user->no_telp;
            if ($request->filled('id_jabatan')) {
                $user->role = Jabatan::where('id_jabatan', $request->id_jabatan)->value('nama_jabatan');
            }
            $user->save();

            $pegawai->id_jabatan = $request->id_jabatan ?? $pegawai->id_jabatan;
            $pegawai->nip = $request->nip ?? $pegawai->nip;
            $pegawai->tanggal_lahir = $request->tanggal_lahir ?? $pegawai->tanggal_lahir;
            $pegawai->save();

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'Pegawai berhasil diubah',
                'data' => ['user' => $user, 'pegawai' => $pegawai,],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pegawai = Pegawai::find($id);

        if (!$pegawai) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
                'errors' => ['id' => 'Pegawai tidak ditemukan'],
            ]);
        }

        $pegawai->delete();
        $user = $pegawai->user;
        $user->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Pegawai berhasil dihapus',
        ]);
    }

    public function getAllKurir()
    {
        $kurir = Pegawai::with([
            'user',
            'jabatan'
        ])->whereHas('jabatan', function ($query) {
            $query->where('jabatan.nama_jabatan', 'Kurir');
        })->get();

        if (!$kurir) {
            return response()->json([
                'message' => 'Kurir tidak ditemukan',
                'errors' => ['id' => 'Kurir tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Kurir',
            'data' => $kurir
        ]);
    }

    public function getPegawai(Request $request)
    {
        $user = $request->user();
        $pegawai = Pegawai::with('user', 'jabatan')->where('id_user', $user->id_user)->first();

        if (!$pegawai) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
                'errors' => ['id' => 'Pegawai tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Pegawai',
            'data' => $pegawai
        ]);
    }

    public function getBarangHunting(Request $request)
    {
        $user = $request->user();
        $hunter = Pegawai::with('user', 'jabatan')->where('id_user', $user->id_user)->first();
        $status = $request->input('status');

        if (!$hunter) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
                'errors' => ['id' => 'Pegawai tidak ditemukan'],
            ], 404);
        }

        $query = Produk::with([
            'kategori',
            'fotoProduk',
            'detailPenitipan.penitipan.penitip.user',
            'detailPenjualan.komisi',
        ])->whereHas('detailPenitipan.penitipan', function ($q) use ($hunter) {
            $q->where('id_hunter', $hunter->id_pegawai);
        });

        if ($status) {
            $query->where(function ($q) use ($status) {
                if ($status == 'Selesai') {
                    $q->whereIn('status_akhir_produk', ['Terjual', 'Produk untuk donasi', 'Didonasikan'])->whereHas('detailPenjualan.komisi');
                } elseif ($status == 'Batal') {
                    $q->whereIn('status_akhir_produk', ['Diambil', 'Akan Diambil', 'Batal']);
                } else { // Selesai
                    $q->where(function ($q2) {
                        $q2->whereNull('status_akhir_produk')
                            ->orWhereIn('status_akhir_produk', ['Sedang Dijual', 'Tidak Laku', 'Terjual', 'Produk untuk donasi', 'Didonasikan']);
                    })->whereDoesntHave('detailPenjualan.komisi');
                }
            });
        }

        $data = $query->get();

        return response()->json([
            'message' => 'Data Barang Hunting',
            'data' => $data
        ]);
    }
}
