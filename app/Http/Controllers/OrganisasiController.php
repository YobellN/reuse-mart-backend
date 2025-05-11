<?php

namespace App\Http\Controllers;

use App\Models\Organisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class OrganisasiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $organisasi = Organisasi::with('user')->get();
        return response()->json([
            'message' => 'Data Organisasi',
            'data' => $organisasi
        ] , 200);
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
        $organisasi = Organisasi::with('user')->find($id);
        
        if (!$organisasi) {
            return response()->json([
                'message' => 'Organisasi tidak ditemukan',
                'errors'  => ['id' => 'Organisasi tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Organisasi',
            'data' => $organisasi
        ]);
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
        $validator = Validator::make($request->all(), [
            'no_sk' => 'sometimes|string',
            'jenis_organisasi' => 'sometimes|string',
            'alamat_organisasi' => 'sometimes|string',
            'nama' => 'sometimes|string|min:3',
            'email' => 'sometimes|email',
            'no_telp' => 'sometimes|regex:/^[0-9]{10,15}$/',
            'password' => 'sometimes|min:8',
        ], [
            'nama.min' => 'Nama minimal 3 karakter',
            'no_telp.regex' => 'Nomor telepon tidak valid',
            'email.email' => 'Email tidak valid',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organisasi = Organisasi::find($id);
        
        if (!$organisasi) {
            return response()->json([
                'message' => 'Organisasi tidak ditemukan',
                'errors' => ['id' => 'Organisasi tidak ditemukan'],
            ], 404);
        }

        $user = $organisasi->user;

        $user->nama = $request->nama ?? $user->nama;
        $user->email = $request->email ?? $user->email;
        $user->no_telp = $request->no_telp ?? $user->no_telp;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $organisasi->no_sk = $request->no_sk ?? $organisasi->no_sk;
        $organisasi->jenis_organisasi = $request->jenis_organisasi ?? $organisasi->jenis_organisasi;
        $organisasi->alamat_organisasi = $request->alamat_organisasi ?? $organisasi->alamat_organisasi;

        $organisasi->save();

        return response()->json([
            'status_code' => 200,
            'message' => 'Organisasi berhasil diperbarui',
            'data' => $organisasi,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $organisasi = Organisasi::find($id);
        
        if (!$organisasi) {
             return response()->json([
                'message' => 'Organisasi tidak ditemukan',
                'errors' => ['id' => 'Organisasi tidak ditemukan'],
            ]);
        }

        $organisasi->delete();
        $user = $organisasi->user;
        $user->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Organisasi berhasil dihapus',
        ]);
    }
}
