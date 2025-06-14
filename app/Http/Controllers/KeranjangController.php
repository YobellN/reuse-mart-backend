<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Keranjang;

class KeranjangController
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
     * Dipanggil ketika membuka keranjang?? atau ketika login aja ya??
     * atau register oke sih
     */
    public function store(Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
            'status' => 'error',
            'message' => 'Anda tidak memiliki izin untuk menambah keranjang'
            ], 403);
        }

        // Cek apakah keranjang sudah ada untuk pembeli ini
        // Jika belum ada, buat keranjang baru
        $pembeli = $user->pembeli;
        if ($pembeli) {
            $keranjang = Keranjang::where('id_pembeli', $pembeli->id_pembeli)->first();
            if (!$keranjang) {
                // Jika keranjang belum ada, buat keranjang baru
                $keranjang = Keranjang::create([
                    'id_pembeli' => $pembeli->id_pembeli,
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Keranjang berhasil dibuat',
                    'data' => $keranjang
                ], 201);
            }else {
                // Jika keranjang sudah ada, kembalikan data keranjang
                return response()->json([
                    'status' => 'success',
                    'message' => 'Keranjang sudah ada',
                    'data' => $keranjang
                ], 200);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan pembeli'
            ], 403);
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
