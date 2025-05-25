<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PembeliController 
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

    public function getPoinPembeli(Request $request)
    {
        $user = $request->user();
        $pembeli = $user->pembeli;

        if (!$pembeli) {
            return response()->json([
                'message' => 'Pembeli tidak ditemukan',
                'errors' => ['id' => 'Pembeli tidak ditemukan']
            ], 404);
        }

        return response()->json([
            'message' => 'Data Poin Pembeli',
            'data' => $pembeli->poin,
        ]);
    }
}
