<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Komisi;
use App\Models\Produk;
use App\Models\Penitip;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\DetailPenjualan;
use App\Services\PenjualanService;

class PenjualanController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $user = $request->user();
        $status_penjualan = $request->query('status_penjualan');

        if ($user->role === 'Pembeli') {
            $id_pembeli = $user->pembeli->id_pembeli;
            $penjualan = Penjualan::with([
                'pembeli.user',
                'detail.produk.kategori',
                'detail.produk.fotoProduk',
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
        } else if ($user->role === 'Gudang') {
            $metode_pengiriman = $request->query('metode_pengiriman');
            $penjualan = Penjualan::with([
                'pembeli.user',
                'detail.produk.kategori',
                'detail.produk.fotoProduk',
                'pengiriman.alamat',
                'pembayaran',
            ])->when($status_penjualan, fn($q) => $q->where('status_penjualan', $status_penjualan))->when($metode_pengiriman, fn($q) => $q->where('metode_pengiriman', $metode_pengiriman))->orderBy('tanggal_penjualan', 'desc')->get();

            if ($penjualan->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data penjualan',
                ], 404);
            }

            return response()->json([
                'message' => 'Riwayat Penjualan',
                'data' => $penjualan,
            ]);
        } else {
            return response()->json([
                'message' => 'Tidak memiliki akses',
            ], 404);
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
            ->whereHas('komisi', function ($query) use ($penitip) {
                $query->where('id_penitip', $penitip->id_penitip);
            })
            ->whereHas('penjualan', function ($query) use ($request) {
                if ($request->status) {
                    $query->where('status_penjualan', $request->status);
                }
            })
            ->get()
            ->map(function ($detail) {
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

    // buat tes saja
    public function tesKomisi($id)
    {
        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $hasil = PenjualanService::hitungKomisi($produk);

        return response()->json([
            'message' => 'Hasil komisi',
            'data' => $hasil
        ], 200);
    }

    // jgn dipake
    public function updateAllKomisi()
    {
        $penjualan = Penjualan::all();
        $hasilKomisi = [];

        foreach ($penjualan as $penj) {
            if ($penj->status_penjualan !== 'Selesai') {
                continue;
            }

            $detail = $penj->detail()->first();

            if (!$detail || !$detail->id_produk) {
                continue;
            }

            $produk = Produk::find($detail->id_produk);
            if (!$produk) {
                continue;
            }

            $komisi = PenjualanService::hitungKomisi($produk);

            $hasilKomisi[] = [
                'penjualan_id' => $penj->id,
                'produk' => $produk->nama_produk ?? 'Tidak ditemukan',
                'komisi' => $komisi,
            ];
        }

        return response()->json([
            'message' => 'Hasil komisi semua penjualan',
            'data' => $hasilKomisi
        ]);
    }

    //buat tes jg
    public function tesTambahSaldo()
    {
        $komisi = Komisi::all();
        $hasil = [];

        foreach ($komisi as $k) {
            $hasil[] = PenjualanService::tambahSaldo($k);
        }

        return response()->json([
            'message' => 'Hasil tambah saldo',
            'data' => $hasil
        ], 200);
    }

    //buat tes jg
    public function tesTambahPoin()
    {
        $penjualan = Penjualan::where('id_penjualan', '25.01.0001')->first();

        if (!$penjualan) {
            return response()->json([
                'message' => 'Penjualan tidak ditemukan',
            ], 404);
        }

        $hasil = PenjualanService::tambahPoin($penjualan);

        return response()->json([
            'message' => 'Hasil tambah poin',
            'data' => $hasil
        ], 200);
    }
}
