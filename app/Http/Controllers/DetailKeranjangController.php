<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailKeranjang;
use App\Models\Keranjang;

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

            // Cek apakah produk sudah ada di detail_keranjang
            $exists = DetailKeranjang::where('id_keranjang', $id_keranjang)
                ->where('id_produk', $request->id_produk)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 'error',
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
                    'status' => 'error',
                    'message' => 'Detail keranjang tidak ditemukan'
                ], 404);
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
        // Mengecek user yang sedang login
        // $user = $request->user();
        // if ($user->role !== 'Pembeli') {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Anda tidak memiliki izin store detail keranjang'
        //     ], 403);
        // }

        // // Validasi input
        // $request->validate([
        //     'id_produk' => 'required|exists:produk,id_produk',
        // ], [
        //     'id_produk.required' => 'ID produk tidak boleh kosong',
        //     'id_produk.exists' => 'ID produk tidak ditemukan',
        // ]);

        // // mengambil id keranjang dari pembeli
        // $pembeli = $user->pembeli;
        // if ($pembeli) {
        //     $keranjang = $pembeli->keranjang;
        //     if (!$keranjang) {
        //         return response()->json([
        //             'status' => 'error',
        //             'message' => 'Keranjang tidak ditemukan'
        //         ], 404);
        //     }
        //     $affected = DetailKeranjang::where('id_keranjang', $keranjang->id_keranjang)
        //         ->where('id_produk', $request->id_produk)
        //         ->update(['status' => $request->status]);

        //     if ($affected === 0) {
        //         return response()->json([
        //             'status' => 'error',
        //             'message' => 'Produk tidak ditemukan di dalam keranjang'
        //         ], 404);
        //     }

        //     return response()->json([
        //         'status' => 'success',
        //         'message' => 'Status produk dalam keranjang berhasil diperbarui'
        //     ], 200);
        // } else {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'User bukan pembeli'
        //     ], 403);
        // }
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
}
