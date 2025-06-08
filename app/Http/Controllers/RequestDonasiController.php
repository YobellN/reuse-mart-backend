<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RequestDonasi;
use Carbon\Carbon;

class RequestDonasiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $id_organisasi = $user->organisasi->id_organisasi;

        if (!$id_organisasi) {
            return response()->json([
                'message' => 'Organisasi tidak ditemukan'
            ], 404);
        }

        $request_donasi = RequestDonasi::with('organisasi.user')->where('id_organisasi', $id_organisasi)->get();

        return response()->json([
            'message' => 'Data Request Donasi',
            'data' => $request_donasi
        ], 200);
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
        $request->validate([
            'deskripsi_request' => 'required|min:10',
        ], [
            'deskripsi_request.required' => 'Deskripsi request tidak boleh kosong',
            'deskripsi_request.min' => 'Deskripsi request minimal 10 karakter',
        ]);

        $user = $request->user();
        $id_organisasi = $user->organisasi->id_organisasi;

        if (!$id_organisasi) {
            return response()->json([
                'message' => 'Organisasi tidak ditemukan'
            ], 404);
        }

        $tanggal_request = Carbon::now();

        $request_donasi = RequestDonasi::create([
            'id_organisasi' => $id_organisasi,
            'deskripsi_request' => $request->deskripsi_request,
            'tanggal_request' => $tanggal_request,
            'status_request' => 0,
        ]);

        return response()->json([
            'message' => 'Request donasi berhasil ditambahkan',
            'data' => $request_donasi
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = request()->user();
        if ($user->role == 'Owner') {
            $request_donasi = RequestDonasi::with('organisasi.user')->where('id_request_donasi', $id)->first();
            return response()->json([
                'message' => 'Data Request Donasi',
                'data' => $request_donasi
            ], 200);
        } else {
            $request_donasi = RequestDonasi::find($id);

            if (!$request_donasi) {
                return response()->json([
                    'message' => 'Request Donasi tidak ditemukan'
                ], 404);
            };

            return response()->json([
                'message' => 'Data Request Donasi',
                'data' => $request_donasi
            ], 200);
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
        $request_donasi = RequestDonasi::find($id);

        if (!$request_donasi) {
            return response()->json([
                'message' => 'Request Donasi tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'deskripsi_request' => 'required|min:10',
        ], [
            'deskripsi_request.required' => 'Deskripsi request tidak boleh kosong',
            'deskripsi_request.min' => 'Deskripsi request minimal 10 karakter',
        ]);

        $request_donasi->update($validated);

        return response()->json([
            'message' => 'Request donasi berhasil diubah',
            'data' => $request_donasi
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $request_donasi = RequestDonasi::find($id);

        if (!$request_donasi) {
            return response()->json([
                'message' => 'Request Donasi tidak ditemukan'
            ], 404);
        }

        $request_donasi->delete();

        return response()->json([
            'message' => 'Request Donasi berhasil dihapus'
        ], 200);
    }

    // untuk menampilkan request donasi yang sedang aktif di dahsboard admin
    public function getActiveRequest(Request $request)
    {
        $request_donasi = RequestDonasi::with('organisasi.user');
        $request_donasi = $request_donasi->where('status_request', 0)->get();

        return response()->json([
            'message' => 'Data Request Donasi',
            'data' => $request_donasi
        ], 200);
    }

    // Rekap request donasi (semua yang belum terpenuhi)
    public function getRekapRequest()
    {
        $request_donasi = RequestDonasi::with('organisasi.user')
            ->where('status_request', 0)
            ->get();

        // Mapping ke struktur custom
        $rekap = $request_donasi->map(function ($item) {
            return [
                'id_organisasi' => $item->organisasi->id_organisasi,
                'nama_organisasi' => $item->organisasi->user->nama,
                'alamat_organisasi' => $item->organisasi->alamat_organisasi,
                'request' => $item->deskripsi_request,
            ];
        });

        return response()->json([
            'message' => 'Data Rekap Request Donasi',
            'data' => $rekap
        ], 200);
    }
}
