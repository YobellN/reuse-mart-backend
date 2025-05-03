<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Penitipan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PenitipanController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $idPenitip     = $user->penitip->id_penitip;
        $status_penitipan   = $request->query('status_penitipan');

        $penitipan = Penitipan::with([
            'penitip.user',
            'qc.user',
            'hunter.user',
            'detailPenitipan.produk.kategori',
        ])->where('id_penitip', $idPenitip)
            ->when($status_penitipan === 'Tenggat Waktu', function ($q) {
                $q->where('status_perpanjangan', 0)
                    ->whereRaw('DATE_ADD(tanggal_penitipan, INTERVAL 30 DAY) <= ?', [Carbon::today()]);
            })
            ->when($status_penitipan === 'Perpanjangan', function ($q) {
                $q->where('status_perpanjangan', 1);
            })
            ->orderByDesc('tanggal_penitipan')->get();


        if ($penitipan->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data penitipan',
            ], 404);
        }

        return response()->json([
            'message' => 'Data Penitipan',
            'data'    => $penitipan,
        ], 200);
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
