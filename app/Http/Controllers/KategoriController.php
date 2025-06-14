<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KategoriProduk;

class KategoriController
{
     public function index()
    {
        $kategori = KategoriProduk::all();
        return response()->json([
            'message' => 'Data Kategori',
            'data' => $kategori
        ]);
    }
}
