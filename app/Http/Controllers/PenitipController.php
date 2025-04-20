<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Penitip;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PenitipController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $penitip = Penitip::with('user')->get();

        return response()->json([
            'message' => 'Data Penitip',
            'data' => $penitip
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|min:3',
            'email' => 'required|email|unique:user,email',
            'password' => 'required|min:8',
            'no_telp' => 'required|regex:/^[0-9]{10,15}$/',
            'nik' => 'required|unique:penitip,nik',
            'foto_ktp' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
        ], [
            'nama.required' => 'Nama tidak boleh kosong',
            'nama.min' => 'Nama minimal 3 karakter',
            'password.required' => 'Password tidak boleh kosong',
            'password.min' => 'Password minimal 8 karakter',
            'no_telp.required' => 'Nomor telepon tidak boleh kosong',
            'no_telp.regex' => 'Nomor telepon tidak valid',
            'email.required' => 'Email tidak boleh kosong',
            'email.email' => 'Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'nik.required' => 'NIK tidak boleh kosong',
            'nik.unique' => 'NIK sudah terdaftar',
            'foto_ktp.required' => 'Foto tidak boleh kosong',
            'foto_ktp.image' => 'Foto harus berupa gambar',
        ]);

        if (!$request->hasFile('foto_ktp')) {
            return response()->json([
                'status_code' => 422,
                'message' => 'File tidak valid',
                'errors' => ['foto_ktp' => 'File tidak valid'],
            ], 422);
        }

        $file = $request->file('foto_ktp');
        $file_name = $request->nik .  '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('foto_ktp', $file_name, 'public');

        $user = User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'no_telp' => $request->no_telp,
            'role' => 'Penitip',
        ]);

        $penitip = Penitip::create([
            'id_user' => $user->id_user,
            'nik' => $request->nik,
            'foto_ktp' => str_replace('foto_ktp/', '', $path),
        ]);

        return response()->json([
            'status_code' => 201,
            'message' => 'Penitip berhasil ditambahkan',
            'data' => [
                'user' => $user,
                'penitip' => $penitip,
            ],
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $penitip = Penitip::with('user')->find($id);

        if (!$penitip) {
            return response()->json([
                'message' => 'Penitip tidak ditemukan',
                'errors' => ['id' => 'Penitip tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Penitip',
            'data' => $penitip
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
        $penitip = Penitip::find($id);

        if (!$penitip) {
            return response()->json([
                'message' => 'Penitip tidak ditemukan',
                'errors' => ['id' => 'Penitip tidak ditemukan'],
            ], 404);
        }

        $user = $penitip->user;

        $request->validate([
            'nama' => 'sometimes|string|min:3',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('user', 'email')->ignore($user->id_user)
            ],
            'password' => 'sometimes|min:8',
            'no_telp' => 'sometimes|regex:/^[0-9]{10,15}$/',
            'nik' => [
                'sometimes',
                Rule::unique('penitip', 'nik')->ignore($penitip->nik)
            ],
            'foto_ktp' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp',
        ], [
            'nama.min' => 'Nama minimal 3 karakter',
            'password.min' => 'Password minimal 8 karakter',
            'no_telp.regex' => 'Nomor telepon tidak valid',
            'email.email' => 'Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'nik.unique' => 'NIK sudah terdaftar',
            'foto_ktp.image' => 'Foto harus berupa gambar',
        ]);

        $user->nama = $request->nama ?? $user->nama;
        $user->email = $request->email ?? $user->email;
        $user->no_telp = $request->no_telp ?? $user->no_telp;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        if ($request->hasFile('foto_ktp')) {
            if ($penitip->foto_ktp && Storage::disk('public')->exists('foto_ktp/' . $penitip->foto_ktp)) {
                Storage::disk('public')->delete('foto_ktp/' . $penitip->foto_ktp);
            }

            $file = $request->file('foto_ktp');
            $filename = $request->nik ?? $penitip->nik . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('foto_ktp', $filename, 'public');

            $penitip->foto_ktp = $filename;
        }

        $penitip->nik = $request->nik ?? $penitip->nik;
        $penitip->save();

        return response()->json([
            'status_code' => 200,
            'message' => 'Penitip berhasil diperbarui',
            'data' => $penitip,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $penitip = Penitip::find($id);

        if (!$penitip) {
            return response()->json([
                'message' => 'Penitip tidak ditemukan',
                'errors' => ['id' => 'Penitip tidak ditemukan'],
            ]);
        }

        if ($penitip->foto_ktp && Storage::disk('public')->exists('foto_ktp/' . $penitip->foto_ktp)) {
            Storage::disk('public')->delete('foto_ktp/' . $penitip->foto_ktp);
        }

        $penitip->delete();
        $user = $penitip->user;
        $user->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Penitip berhasil dihapus',
        ]);
    }
}
