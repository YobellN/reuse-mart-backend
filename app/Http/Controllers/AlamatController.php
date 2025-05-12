<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Alamat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlamatController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $pembeli = $user->pembeli;

        if (!$pembeli) {
            return response()->json([
                'message' => 'Pembeli tidak ditemukan',
                'errors' => ['id' => 'Pembeli tidak ditemukan']
            ], 404);
        }

        $alamat = Alamat::where('id_pembeli', $pembeli->id_pembeli)->get();

        return response()->json([
            'message' => 'Data Alamat',
            'data' => $alamat
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $pembeli = $user->pembeli;

        if (!$pembeli) {
            return response()->json([
                'message' => 'Pembeli tidak ditemukan',
                'errors' => ['id' => 'Pembeli tidak ditemukan']
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:100',
            'kabupaten_kota' => 'required|string|max:100',
            'kecamatan' => 'required|string|max:100',
            'kode_pos' => 'required|string|max:10',
            'detail_alamat' => 'sometimes|string|max:255',
        ], [
            'label.required' => 'Label alamat harus diisi',
            'kabupaten_kota.required' => 'Kota harus diisi',
            'kecamatan.required' => 'Kecamatan harus diisi',
            'kode_pos.required' => 'Kode pos harus diisi',
            'detail_alamat.string' => 'Detail alamat harus berupa string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Jika alamat pertama, maka set alamat utama, jika enggak, set alamat utama ke 0
        $alamatUtama = Alamat::where('id_pembeli', $pembeli->id_pembeli)->doesntExist();
        if ($alamatUtama) {
            $request->merge(['alamat_utama' => 1]);
        } else {
            $request->merge(['alamat_utama' => 0]);
        }

        $alamat = Alamat::create([
            'id_pembeli' => $pembeli->id_pembeli,
            'label' => $request->label,
            'kabupaten_kota' => $request->kabupaten_kota,
            'kecamatan' => $request->kecamatan,
            'kode_pos' => $request->kode_pos,
            'alamat_utama' => $request->alamat_utama,
            'detail_alamat' => $request->detail_alamat,
        ]);

        return response()->json([
            'message' => 'Alamat berhasil ditambahkan',
            'data' => $alamat
        ], 201);
    }

    public function show(string $id, Request $request)
    {
        $user = $request->user();
        $pembeli = $user->pembeli;

        if (!$pembeli) {
            return response()->json([
                'message' => 'Pembeli tidak ditemukan',
                'errors' => ['id' => 'Pembeli tidak ditemukan']
            ], 404);
        }

        $alamat = Alamat::where('id_alamat', $id)
            ->where('id_pembeli', $pembeli->id_pembeli)
            ->first();

        if (!$alamat) {
            return response()->json([
                'message' => 'Alamat tidak ditemukan atau tidak dimiliki oleh pembeli ini',
                'errors' => ['id_alamat' => 'Alamat tidak ditemukan atau tidak dimiliki oleh pembeli ini']
            ], 404);
        }

        return response()->json([
            'message' => 'Data Alamat',
            'data' => $alamat
        ]);
    }

    public function update(string $id, Request $request)
    {
        $user = $request->user();
        $pembeli = $user->pembeli;

        if (!$pembeli) {
            return response()->json([
                'message' => 'Pembeli tidak ditemukan',
                'errors' => ['id' => 'Pembeli tidak ditemukan']
            ], 404);
        }

        // Validate the request
    
        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|string',
            'kabupaten_kota' => 'sometimes|string',
            'kecamatan' => 'sometimes|string',
            'kode_pos' => 'sometimes|string',
            'detail_alamat' => 'sometimes|string',
        ], [
            'label.string' => 'Label alamat harus berupa string',
            'kabupaten_kota.string' => 'Kota harus berupa string',
            'kecamatan.string' => 'Kecamatan harus berupa string',
            'kode_pos.string' => 'Kode pos harus berupa string',
            'alamat_utama.in' => 'Alamat utama harus bernilai 0 atau 1',
            'detail_alamat.string' => 'Detail alamat harus berupa string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi error',
                'errors' => $validator->errors()
            ], 422);
        }

        $alamat = Alamat::find($id);

        if (!$alamat) {
            return response()->json([
                'message' => 'Alamat tidak ditemukan',
                'errors' => ['id' => 'Alamat tidak ditemukan']
            ], 404);
        }

        $alamat->label = $request->label ?? $alamat->label;
        $alamat->kabupaten_kota = $request->kabupaten_kota ?? $alamat->kabupaten_kota;
        $alamat->kecamatan = $request->kecamatan ?? $alamat->kecamatan;
        $alamat->kode_pos = $request->kode_pos ?? $alamat->kode_pos;
        $alamat->detail_alamat = $request->detail_alamat ?? $alamat->detail_alamat;
        
        $alamat->save();

        return response()->json([
            'message' => 'Alamat berhasil diperbarui',
            'data' => $alamat
        ]);
    }

    public function destroy(string $id)
    {
        $alamat = Alamat::find($id);

        if (!$alamat) {
            return response()->json([
                'message' => 'Alamat tidak ditemukan',
                'errors' => ['id' => 'Alamat tidak ditemukan']
            ], 404);
        }

        if ($alamat->alamat_utama) {
            return response()->json([
                'message' => 'Alamat utama tidak dapat dihapus',
                'errors' => ['alamat_utama' => 'Alamat utama tidak dapat dihapus']
            ], 422);
        }

        $alamat->delete();

        return response()->json([
            'message' => 'Alamat berhasil dihapus'
        ]);
    }

    // Mengupdate alamat utama berdasarkan ID
    public function gantiAlamatUtama(string $id, Request $request)
    {
        $user = $request->user();
        $pembeli = $user->pembeli;

        if (!$pembeli) {
            return response()->json([
                'message' => 'Pembeli tidak ditemukan',
                'errors' => ['id' => 'Pembeli tidak ditemukan']
            ], 404);
        }

        // Cek apakah alamat dengan ID tersebut milik pembeli
        $alamat = Alamat::where('id_alamat', $id)
            ->where('id_pembeli', $pembeli->id_pembeli)
            ->first();

        if (!$alamat) {
            return response()->json([
                'message' => 'Alamat tidak ditemukan atau tidak dimiliki oleh pembeli ini',
                'errors' => ['id_alamat' => 'Alamat tidak ditemukan atau tidak dimiliki oleh pembeli ini']
            ], 404);
        }

        // Ubah alamat utama yang sebelumnya bernilai 1 menjadi 0
        Alamat::where('id_pembeli', $pembeli->id_pembeli)
            ->where('alamat_utama', 1)
            ->update(['alamat_utama' => 0]);

        // Set alamat yang diminta menjadi alamat utama
        $alamat->alamat_utama = 1;
        $alamat->save();

        return response()->json([
            'message' => 'Alamat utama berhasil diperbarui',
            'data' => $alamat
        ]);
    }

}
