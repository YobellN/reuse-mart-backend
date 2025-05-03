<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()) {
            $produk = Produk::with('kategori')->get();
            return response()->json([
                'message' => 'Data Produk',
                'data' => $produk
            ]);
        }

        $user = $request->user();

        if ($user->role === 'Penitip') {
            $status_akhir_produk = $request->query('status_akhir_produk');
            $id_penitip = $user->penitip->id_penitip;
            $produk = Produk::select('produk.*')
                ->join('detail_penitipan', 'produk.id_produk', '=', 'detail_penitipan.id_produk')
                ->join('penitipan', 'detail_penitipan.id_penitipan', '=', 'penitipan.id_penitipan')
                ->where('penitipan.id_penitip', $id_penitip)
                ->when(
                    $status_akhir_produk,
                    fn($q) =>
                    $q->where('produk.status_akhir_produk', $status_akhir_produk)
                )
                ->distinct()
                ->get();


            return response()->json([
                'message' => 'Data Produk',
                'data' => $produk
            ]);
        }
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
