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
        $status_penjualan = $request->query('status_penjualan');

        if ($user->role !== 'Pembeli') {
            return response()->json([
                'message' => 'Akses ditolak: hanya role Pembeli yang diperbolehkan',
            ], 403);
        }

        $id_pembeli = $user->pembeli->id_pembeli;
        $penjualan = Penjualan::with([
            'pembeli.user',
            'detail.produk.kategori',
            'pengiriman.alamat',
            'pembayaran',
        ])->where('id_pembeli', $id_pembeli)->when($status_penjualan, fn($q) => $q->where('status_penjualan', $status_penjualan))->orderBy('tanggal_penjualan', 'desc')->get();

        if ($penjualan->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data penjualan',
            ], 404);
        }

        return response()->json([
            'message' => 'Riwayat Penjualan',
            'data' => $penjualan,
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
