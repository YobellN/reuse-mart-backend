<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailPenjualan;

class DetailPenjualanController
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
        // TODO:
        // JANGAN LUPA VALIDASI PRODUK YG UDH TERJUAL ATAU BELUM, JADI GABISA DIBELI DOUBLE
        
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'errors' => 'Anda tidak memiliki izin store detail penjualan',
                'message' => 'Anda tidak memiliki izin store detail penjualan'
            ], 403);
        }

        $request->validate([
            'id_penjualan' => 'required',
            'id_produk' => 'required|string',
        ], [
            'id_penjualan.required' => 'ID Penjualan tidak boleh kosong',
            'id_produk.required' => 'ID Produk tidak boleh kosong',
        ]);

        // membuat detail penjualan dengan inputan dari request berupa 'id_penjualan', 'id_produk'
        $detailPenjualan = DetailPenjualan::create([
            'id_penjualan' => $request->id_penjualan,
            'id_produk' => $request->id_produk
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail penjualan berhasil ditambahkan',
            'data' => $detailPenjualan
        ], 200);
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
