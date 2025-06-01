<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProdukController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $kategori = $request->query('kategori');
        $limit = $request->query('limit');
        $status_akhir_produk = $request->query('status_akhir_produk');
        $search = $request->query('search');

        if (!$limit || !is_numeric($limit) || $limit < 1) {
            $limit = 10;
        }

        $produk = Produk::with([
            'kategori',
            'detailPenitipan.penitipan.penitip.user',
            'fotoProduk' => fn($q) => $q->orderBy('thumbnail', 'desc')
        ])
            ->when($kategori, fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama_kategori', $kategori)))
            ->when(
                $status_akhir_produk !== null,
                fn($q) => $q->where('status_akhir_produk', $status_akhir_produk),
                fn($q) => $q->whereNull('status_akhir_produk')
            )
            ->when($search, fn($q) => $q->where('nama_produk', 'like', '%' . $search . '%'))
            ->paginate($limit);

        if ($produk->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data produk',
            ], 404);
        };

        return response()->json([
            'message' => 'Data Produk',
            'data' => $produk
        ]);
    }

    // get All Produk
    public function getAllProduk()
    {
        $produk = Produk::with([
            'kategori',
            'detailPenitipan.penitipan.penitip.user',
            'fotoProduk'
        ])->get();

        return response()->json([
            'message' => 'Data Produk',
            'data' => $produk
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
        $produk = Produk::with([
            'kategori',
            'detailPenitipan.penitipan.penitip.user',
            'fotoProduk'
        ])->find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        };

        return response()->json([
            'message' => 'Data Produk',
            'data' => $produk
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getProdukByPenitip(string $id)
    {
        $produk = Produk::with([
            'kategori',
            'detailPenitipan.penitipan.penitip.user',
            'fotoProduk'
        ])
        ->join('detail_penitipan', 'produk.id_produk', '=', 'detail_penitipan.id_produk')
        ->join('penitipan', 'detail_penitipan.id_penitipan', '=', 'penitipan.id_penitipan')
        ->where('penitipan.id_penitip', $id)
        ->whereNull('produk.status_akhir_produk')
        ->limit(6)->get();


        if ($produk->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data produk',
            ], 404);
        };

        return response()->json([
            'message' => 'Data Produk',
            'data' => $produk
        ]);
    }

    public function getProdukUntukDonasi()
    {
        $produk = Produk::with(['kategori', 'fotoProduk'])->where('status_akhir_produk', "Produk untuk donasi")->get();

        return response()->json([
            'message' => 'Data Produk Untuk Donasi',
            'data' => $produk
        ]);
    }
}
