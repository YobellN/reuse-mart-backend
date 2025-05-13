<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donasi;
use App\Models\RequestDonasi;
use App\Models\Penitip;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;

class DonasiController 
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $donasi = Donasi::with('requestDonasi', 'produk')->get();

        return response()->json([
            'message' => 'Data Donasi',
            'data' => $donasi
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
         DB::beginTransaction();

         try {
            $request->validate([
                'id_request_donasi' => 'required|exists:request_donasi,id_request_donasi',
                'id_produk' => 'required|exists:produk,id_produk',
                'tanggal_donasi' => 'required|date',
                'nama_penerima' => 'required|string|min:3',
            ],[
                'id_request_donasi.required' => 'ID request donasi tidak boleh kosong',
                'id_request_donasi.exists' => 'ID request donasi tidak ditemukan',
                'id_produk.required' => 'ID produk tidak boleh kosong',
                'id_produk.exists' => 'ID produk tidak ditemukan',
                'tanggal_donasi.required' => 'Tanggal donasi tidak boleh kosong',
                'tanggal_donasi.date' => 'Tanggal donasi harus berupa tanggal',
                'nama_penerima.required' => 'Nama penerima tidak boleh kosong',
                'nama_penerima.min' => 'Nama penerima minimal 3 karakter',
            ]);

            $produk = Produk::find($request->id_produk);
            if(!$produk){
                return response()->json([
                    'message' => 'Produk tidak ditemukan'
                ], 404);
            }
            $harga_produk = $produk->harga_produk;
            $total_poin = round($harga_produk / 10000);

            //store dulu data donasinya
            $donasi = Donasi::create([
                'id_request_donasi' => $request->id_request_donasi,
                'id_produk' => $request->id_produk,
                'tanggal_donasi' => $request->tanggal_donasi,
                'nama_penerima' => $request->nama_penerima,
                'total_poin' => $total_poin
            ]);

            //ini untuk update poin penitip
            $penitip = $produk->detailPenitipan->penitipan->penitip;
            $penitip->increment('poin', $total_poin);
            $penitip->save();

            $request_donasi = RequestDonasi::find($request->id_request_donasi);
            if(!$request_donasi){
                return response()->json([
                    'message' => 'Request donasi tidak ditemukan'
                ], 404);
            }
            $request_donasi->status_request = 1; 
            $request_donasi->save(); //status req nya kita ganti

            $produk->status_akhir_produk = "Didonasikan";
            $produk->save(); //status produknya juga diganti


            DB::commit();

            return response()->json([
                'message' => 'Donasi berhasil ditambahkan',
                'data' => $donasi
            ], 200);
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
