<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailKeranjang;
use App\Models\Keranjang;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class DetailKeranjangController
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
     * dipakai untuk tombol tambah produk ke keranjang
     */
    public function store(Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin store detail keranjang'
            ], 403);
        }

        // Validasi input
        $request->validate([
            'id_produk' => 'required|exists:produk,id_produk',
        ], [
            'id_produk.required' => 'ID produk tidak boleh kosong',
            'id_produk.exists' => 'ID produk tidak ditemukan',
        ]);

        // mengambil id keranjang dari pembeli
        $pembeli = $user->pembeli;
        if ($pembeli) {
            $keranjang = Keranjang::where('id_pembeli', $pembeli->id_pembeli)->first();
            $id_keranjang = $keranjang->id_keranjang;

            // ngecek apakah stok produk masih ada
            $produk = Produk::where('id_produk', $request->id_produk)->first();
            if ($produk->status_ketersediaan == 0) {
                return response()->json([
                    'errors' => 'Stok produk sudah habis',
                    'message' => 'Stok produk sudah habis'
                ], 409);
            }

            // Cek apakah produk sudah ada di detail_keranjang
            $exists = DetailKeranjang::where('id_keranjang', $id_keranjang)
                ->where('id_produk', $request->id_produk)
                ->exists();

            if ($exists) {
                return response()->json([
                    'errors' => 'Produk sudah ada di dalam keranjang',
                    'message' => 'Produk sudah ada di dalam keranjang'
                ], 409);
            }

            $detailKeranjang = DetailKeranjang::create([
                'id_keranjang' => $id_keranjang,
                'id_produk' => $request->id_produk
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Detail keranjang berhasil ditambahkan',
                'data' => $detailKeranjang
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan pembeli'
            ], 403);
        }
    }

    /**
     * Display the specified resource.
     * Menampilkan detail keranjang berdasarkan id_keranjang yang diambil dari user yang sedang login
     */
    public function show(Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk melihat detail keranjang'
            ], 403);
        }

        // mengambil id keranjang dari pembeli
        $pembeli = $user->pembeli;
        // kalau ketemu pembeli, cek keranjang yang dimiliki pembeli, lalu ambil id_keranjang
        if ($pembeli) {
            $keranjang = Keranjang::where('id_pembeli', $pembeli->id_pembeli)->first();
            $id_keranjang = $keranjang->id_keranjang;
            // mengambil detail keranjang berdasarkan id_keranjang
            $detailKeranjang = DetailKeranjang::with('produk')->where('id_keranjang', $id_keranjang)->get();
            if ($detailKeranjang->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ], 200);
            }
            return response()->json([
                'status' => 'success',
                'data' => $detailKeranjang
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan pembeli'
            ], 403);
        }
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
    public function update(Request $request)
    {
        // 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(request $request, $id)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin store detail keranjang'
            ], 403);
        }

        // mengambil id keranjang dari pembeli
        $pembeli = $user->pembeli;
        if ($pembeli) {
            $keranjang = $pembeli->keranjang;
            if (!$keranjang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Keranjang tidak ditemukan'
                ], 404);
            }
            $affected = DetailKeranjang::where('id_keranjang', $keranjang->id_keranjang)
                ->where('id_produk', $id)
                ->delete();

            if ($affected === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produk tidak ditemukan di dalam keranjang'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Produk dalam keranjang berhasil dihapus'
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan pembeli'
            ], 403);
        }
    }

    /**
     * Fungsi ini untuk menghapus semua produk dalam detail keranjang yang statusnya 1
     * Dipakai (rencananya) ketika pembayaran berhasil, setelah dimasukkan ke dalam penjualan
     */
    public function destroyAll(Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin store detail keranjang'
            ], 403);
        }

        // mengambil id keranjang dari pembeli
        $pembeli = $user->pembeli;
        if ($pembeli) {
            $keranjang = $pembeli->keranjang;
            if (!$keranjang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Keranjang tidak ditemukan'
                ], 404);
            }
            $affected = DetailKeranjang::where('id_keranjang', $keranjang->id_keranjang)->where('status', 1)
                ->delete();

            if ($affected === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produk tidak ditemukan di dalam keranjang'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Semua produk dalam keranjang berhasil dihapus'
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan pembeli'
            ], 403);
        }
    }

    // Mengambil semua total harga dari detail keranjang
    public function getTotalHarga(Request $request)
    {
        // Validasi input poinKepakai
        $validated = $request->validate([
            'poinKepakai' => 'required|integer|min:0',
            'metode_pengambilan' => 'required|in:Ambil di gudang,Antar Kurir',
        ]);
        $poinKepakai = $validated['poinKepakai'];
        $metodePengambilan = $validated['metode_pengambilan'];

        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk melihat total harga'
            ], 403);
        }

        // mengambil id keranjang dari pembeli
        $pembeli = $user->pembeli;
        if ($pembeli) {
            $keranjang = Keranjang::where('id_pembeli', $pembeli->id_pembeli)->first();
            if (!$keranjang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Keranjang tidak ditemukan'
                ], 404);
            }

            // Mengambil semua detail keranjang beserta produk
            $detailKeranjang = DetailKeranjang::with('produk')
                ->where('id_keranjang', $keranjang->id_keranjang)
                ->get();

            // Menjumlahkan total harga semua produk di keranjang
            $totalHarga = $detailKeranjang->sum(function ($item) {
                return $item->produk->harga_produk ?? 0;
            });

            // menghitung poin yang diperoleh dari total harga
            // 1 poin = 10.000, bonus 20% jika > 500.000
            $poin = floor($totalHarga / 10000);
            if ($totalHarga > 500000) {
                $poin += floor($poin * 0.2); // bonus 20%
            }

            // menghitung ongkir jika metode pengambilan adalah Antar Kurir
            $ongkir = 0;
            if ($metodePengambilan === 'Antar Kurir') {
                // Ongkir gratis jika total >= 1.5 juta, selain itu 100 ribu
                $ongkir = $totalHarga >= 1500000 ? 0 : 100000;
            }

            // mengurangkan poinKepakai dari totalHarga jika ada
            $diskon = $poinKepakai * 100; // 1 poin = 100 diskon
            $totalHargaSetelahDiskon = max($totalHarga - $diskon, 0); // jaga-jaga agar tidak minus

            // total akhir termasuk ongkir
            $totalAkhir = $totalHargaSetelahDiskon + $ongkir;

            return response()->json([
                'status' => 'success',
                'message' => 'Total harga berhasil dihitung',
                'data' => [
                    'poin' => $poin,
                    'poin_dipakai' => $poinKepakai,
                    'diskon' => $diskon,
                    'ongkir' => $ongkir,
                    'total_harga' => $totalHarga,
                    'total_akhir' => $totalAkhir
                ]
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan pembeli'
            ], 403);
        }
    }

    // Fungsi untuk mengecek apakah dalam keranjang ada barang yang stoknya udah habis (mana tau ke CO duluan)
    public function cekStok(Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk mengecek stok'
            ], 403);
        }

        // mengambil id keranjang dari pembeli
        $pembeli = $user->pembeli;
        if ($pembeli) {
            $keranjang = Keranjang::where('id_pembeli', $pembeli->id_pembeli)->first();
            if (!$keranjang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Keranjang tidak ditemukan'
                ], 404);
            }

            // Mengambil semua detail keranjang beserta produk
            $detailKeranjang = DetailKeranjang::with('produk')
                ->where('id_keranjang', $keranjang->id_keranjang)
                ->get();

            // Mengecek apakah ada produk yang stoknya udah habis atau tidak tersedia
            $produkHabis = $detailKeranjang->filter(function ($item) {
                return $item->produk->status_ketersediaan == 0;
            });

            if ($produkHabis->isNotEmpty()) {
                // Menghapus produk yang stoknya habis dari detail keranjang
                foreach ($produkHabis as $item) {
                    DetailKeranjang::where('id_keranjang', $item->id_keranjang)
                        ->where('id_produk', $item->id_produk)
                        ->delete();
                }

                return response()->json([
                    'status' => 'error',
                    'message' => 'Stok produk sudah habis',
                    'data' => $produkHabis
                ], 400);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Stok produk masih ada'
                ], 200);
            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan pembeli'
            ], 403);
        }
    }
}
