<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;

class PenjualanController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $user = $request->user();
        $status_penjualan = $request->query('status_penjualan') ?? null;

        $user->role == 'Pembeli';
        $pembeli = $user->pembeli;
        $query = Penjualan::with([
            'pembeli.user',
            'detail.produk.kategori',
            'pengiriman.alamat',
            'pembayaran',
        ])->where('id_pembeli', $pembeli->id_pembeli);

        if ($status_penjualan) {
            $query->where('status_penjualan', $status_penjualan);
        }

        $penjualans = $query->orderBy('tanggal_penjualan', 'desc')->get();

        if ($penjualans->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data penjualan',
            ], 404);
        }

        return response()->json([
            'message' => 'Riwayat Penjualan',
            'data' => $penjualans,
        ]);
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
}
