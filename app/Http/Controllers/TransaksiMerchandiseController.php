<?php

namespace App\Http\Controllers;

use App\Models\Merchandise;
use App\Models\TransaksiMerchandise;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransaksiMerchandiseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transaksiMerchandise = TransaksiMerchandise::with('merchandise', 'pembeli', 'pembeli.user')->get();

        return response()->json([
            'message' => 'Data Transaksi Merchandise',
            'data' => $transaksiMerchandise
        ], 200);
    }

    public function merchBelumDiambil()
    {
        $transaksiMerchandise = TransaksiMerchandise::with('merchandise', 'pembeli', 'pembeli.user')
        ->where('status_transaksi', 'Diproses')
        ->get();

        return response()->json([
            'message' => 'Data Transaksi Merchandise',
            'data' => $transaksiMerchandise
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
    public function store(Request $request, $id)
    {
        $user = $request->user();
        $pembeli = $user->pembeli;

        $merchandise = Merchandise::where('id_merchandise', $id)->first();

        if (!$merchandise) {
            return response()->json(['message' => 'Merchandise tidak ditemukan'], 404);
        }

        if ($merchandise->stok < 1) {
            return response()->json(['message' => 'Stok merchandise habis'], 400);
        }

        if ($pembeli->poin < $merchandise->poin_penukaran) {
            return response()->json(['message' => 'Poin Anda tidak mencukupi'], 400);
        }

        TransaksiMerchandise::create([
            'id_pembeli' => $pembeli->id_pembeli,
            'id_merchandise' => $merchandise->id_merchandise,
            'tanggal_transaksi' => now(),
            'status_transaksi' => 'Diproses'
        ]);

        $merchandise->update([
            'stok' => $merchandise->stok - 1
        ]);

        $pembeli->update([
            'poin' => $pembeli->poin - $merchandise->poin_penukaran
        ]);

        return response()->json(['message' => 'Berhasil klaim merchandise'], 201);
    }

    public function updateStatusSelesai(string $id)
    {
        $transaksi = TransaksiMerchandise::find($id);

        if (!$transaksi) {
            return response()->json(['message' => 'Transaksi merchandise tidak ditemukan'], 404);
        }

        $transaksi->update([
            'status_transaksi' => 'Selesai',
            'tanggal_pengambilan' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil diperbarui menjadi selesai',
            'data' => $transaksi,
        ]);
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
