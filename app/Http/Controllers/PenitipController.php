<?php

namespace App\Http\Controllers;

use App\Models\Penitip;
use Illuminate\Http\Request;

class PenitipController extends Controller
{
    public function index() {
        $penitip = Penitip::all();

        return response()->json([
            'message' => 'Data Penitip',
            'data' => $penitip
        ], 200);
    }
}
