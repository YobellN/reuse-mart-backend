<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Penitipan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PenitipanController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'Penitip') {
            $idPenitip = $user->penitip->id_penitip;

            $produk = Produk::select(
                'produk.*',
                'kategori_produk.nama_kategori as kategori',
                'foto_pertama.path_foto as foto',
                'penitipan.id_penitipan',
                'penitipan.tanggal_penitipan',
                'penitipan.tenggat_penitipan',
                'penitipan.tenggat_pengambilan',
                'penitipan.status_perpanjangan',
                'qc_user.nama as qc',
                'hunter_user.nama as hunter',
                'detail_penitipan.jadwal_pengambilan'
            )
                ->join('kategori_produk', 'produk.id_kategori', '=', 'kategori_produk.id_kategori')
                ->leftJoin(DB::raw('(SELECT id_produk, MAX(path_foto) as path_foto
                                    FROM foto_produk
                                    WHERE thumbnail = 1
                                    GROUP BY id_produk
                                    ) as foto_pertama'), 'foto_pertama.id_produk', '=', 'produk.id_produk')
                ->join('detail_penitipan', 'produk.id_produk', '=', 'detail_penitipan.id_produk')
                ->join('penitipan', 'detail_penitipan.id_penitipan', '=', 'penitipan.id_penitipan')
                ->join('pegawai as qc', 'penitipan.id_qc', '=', 'qc.id_pegawai')
                ->join('user as qc_user', 'qc.id_user', '=', 'qc_user.id_user')
                ->join('pegawai as hunter', 'penitipan.id_hunter', '=', 'hunter.id_pegawai', 'left')
                ->join('user as hunter_user', 'hunter.id_user', '=', 'hunter_user.id_user', 'left')
                ->where('penitipan.id_penitip', $idPenitip)
                ->orderBy('penitipan.tanggal_penitipan', 'desc')
                ->distinct()
                ->get();

            return response()->json([
                'message' => 'Berhasil mendapatkan data penitipan',
                'data' => $produk
            ], 200);
        }

        return response()->json([
            'message' => 'Role anda bukan penitip',
        ], 404);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function konfirmasiPerpanjangan(string $id)
    {
        $penitipan = Penitipan::find($id);
        if (!$penitipan) {
            return response()->json([
                'message' => 'Penitipan tidak ditemukan',
            ], 404);
        }

        if ($penitipan->status_perpanjangan == 1) {
            return response()->json([
                'message' => 'Penitipan hanya dapat di perpanjang 1 kali',
            ], 404);
        }

        $penitipan->status_perpanjangan = 1;
        $tenggat = Carbon::parse($penitipan->tenggat_penitipan);
        $penitipan->tenggat_penitipan = $tenggat->addDays(30);
        $penitipan->tenggat_pengambilan = $tenggat->copy()->addDays(7);
        $penitipan->save();

        return response()->json([
            'message' => 'Penitipan berhasil di perpanjang',
            'data' => $penitipan
        ], 200);
    }

    public function konfirmasiPengambilan(string $id)
    {
        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $produk->status_akhir_produk = "Akan Diambil";
        $produk->save();

        return response()->json([
            'message' => 'Produk berhasil dikonfirmasi pengambilan',
            'data' => $produk
        ], 200);
    }

    public function konfirmasiDonasi(string $id)
    {
        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $produk->status_akhir_produk = "Produk untuk donasi";
        $produk->save();

        return response()->json([
            'message' => 'Produk berhasil dikonfirmasi untuk donasi',
            'data' => $produk
        ], 200);
    }
}
