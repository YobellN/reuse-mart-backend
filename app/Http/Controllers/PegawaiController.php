<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index() {
        $pegawai = Pegawai::with('user', 'jabatan')->get();
        return response()->json([
            'message' => 'Data Pegawai',
            'data' => $pegawai
        ], 200);
    }
}
