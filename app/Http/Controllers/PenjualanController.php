<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Penitip;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
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

    // Mengambil seluruh data penjualan berdasarkan user yang terautentikasi
    public function getDetailPenjualanByPenitip(Request $request)
    {
        $user = $request->user();
        $penitip = Penitip::with('user')->where('id_user', $user->id_user)->first(); 
    
        $request->validate([
            'status' => 'nullable|in:Menunggu Pembayaran,Diproses,Disiapkan,Dikirim,Selesai,Batal,Hangus'
        ], [
            'status.in' => 'Status tidak valid'
        ]);
    
        if (!$penitip) {
            return response()->json([
                'message' => 'Akun bukan penitip',
            ], 404);
        }
    

        $details = DetailPenjualan::with(['penjualan', 'produk', 'komisi'])
            ->whereHas('komisi', function($query) use ($penitip) {
                $query->where('id_penitip', $penitip->id_penitip);
            })
            ->whereHas('penjualan', function($query) use ($request) {
                if ($request->status) {
                    $query->where('status_penjualan', $request->status);
                }
            })
            ->get()
            ->map(function($detail) {
                return [
                    'id_penjualan' => $detail->penjualan->id_penjualan,
                    'tanggal_penjualan' => $detail->penjualan->tanggal_penjualan,  
                    'id_pembeli' => $detail->penjualan->id_pembeli,
                    'komisi_penitip' => $detail->komisi->komisi_penitip,
                    'bonus_penitip' => $detail->komisi->bonus_penitip,
                    'id_produk' => $detail->produk->id_produk,
                    'nama_produk' => $detail->produk->nama_produk
                ];
            });

        if ($details->isEmpty()) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Data detail penjualan berdasarkan user',
            'data' => $details
        ], 200);
    }
}
