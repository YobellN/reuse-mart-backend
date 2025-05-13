<?php

namespace App\Http\Controllers;

use App\Models\Diskusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class DiskusiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       //
    }

    public function getDiskusiProduk(string $id)
    {
        try {
            // Ambil data diskusi dan gunakan 'values()' untuk mengubah ke array
            $diskusi = Diskusi::with(['user', 'produk'])
                ->where('id_produk', $id)
                ->get()
                ->values(); // Mengubah koleksi menjadi array

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengambil semua data diskusi',
                'data' => $diskusi
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data diskusi: ' . $e->getMessage()
            ], 500);
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
        try {
            $validator = Validator::make($request->all(), [
                'pesan' => 'required|string',
                'id_produk' => 'required|exists:produk,id_produk',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            if (!in_array($user->role, ['Pembeli', 'CS'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menambah diskusi'
                ], 403);
            }

            $diskusi = Diskusi::create([
                'id_user' => $user->id_user,
                'id_produk' => $request->id_produk,
                'pesan' => $request->pesan,
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menambah diskusi',
                'data' => $diskusi
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambah diskusi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $diskusi = Diskusi::with(['user', 'produk'])->find($id);

            if (!$diskusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Diskusi tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengambil detail diskusi',
                'data' => $diskusi
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail diskusi: ' . $e->getMessage()
            ], 500);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $diskusi = Diskusi::find($id);

            if (!$diskusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Diskusi tidak ditemukan'
                ], 404);
            }

            $diskusi->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus diskusi'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus diskusi: ' . $e->getMessage()
            ], 500);
        }
    }
}
