<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Pengiriman;
use Illuminate\Http\Request;

class PengirimanController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $pengiriman = Pengiriman::with([
            'alamat',
            'kurir.jabatan',
            'kurir.user',
            'penjualan'
        ])->find($id);

        if (!$pengiriman) {
            return response()->json([
                'message' => 'Pengiriman tidak ditemukan',
                'errors' => ['id' => 'Pengiriman tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Detail Pengiriman',
            'data' => $pengiriman
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
        $pengiriman = Pengiriman::with([
            'alamat',
            'kurir',
            'penjualan'
        ])->find($id);

        $validated = $request->validate([
            'id_kurir' => 'sometimes|exists:pegawai,id_pegawai',
            'jadwal_pengiriman' => 'sometimes|date',
        ], [
            'id_kurir.exists' => 'Kurir tidak ditemukan',
            'jadwal_pengiriman.date' => 'Jadwal pengiriman tidak valid',
        ]);

        $pengiriman->update([
            'id_kurir' => $validated['id_kurir'],
            'jadwal_pengiriman' => $validated['jadwal_pengiriman'],
            'status_pengiriman' => 'Menunggu Kurir',
        ]);

        $pengiriman->penjualan()->update([
            'status_penjualan' => 'Dikirim'
        ]);

        $penjualan = Penjualan::with([
            'pembeli.user',
            'detail.produk.kategori',
            'detail.produk.fotoProduk',
            'pengiriman.alamat',
            'pembayaran',
        ])->find($pengiriman->id_penjualan);

        return response()->json([
            'message' => 'Pengiriman berhasil dijadwalkan',
            'data' => $penjualan
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
