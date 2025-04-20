<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController 
{
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:user',
            'password' => 'required'
        ], [
            'email.required' => 'Email tidak boleh kosong',
            'email.email' => 'Email tidak valid',
            'email.exists' => 'Email tidak terdaftar',
            'password.required' => 'Password tidak boleh kosong'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => "validasi gagal",
                'errors' => $validator->errors()
            ], 422, ['Content-Type' => 'application/json']);
        }

        $user = User::where('email', $request->email)->first();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'validasi gagal',
                'errors' => ['password' => 'password salah']
            ], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Berhasil login',
            'data' => [
                'user' => $user,
                'access_token' => $token
            ],
        ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|min:3',
            'email' => 'required|email|unique:user',
            'password' => 'required|min:8',
            'no_telp' => 'required|regex:/^[0-9]{10,15}$/',
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
        ]);

        $user = User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'no_telp' => $request->no_telp,
            'role' => 'Pembeli',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Berhasil daftar',
            'data' => [
                'user' => $user,
                'access_token' => $token
            ],
        ]);
    }

    public function getUser(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if ($user->role == 'Admin' || $user->role == 'Hunter' || $user->role == 'CS' || $user->role == 'Kurir' || $user->role == 'Gudang' || $user->role == 'Owner') {
            $user = User::with('pegawai')->find($user->id_user);
            return response()->json([
                'message' => 'Data user',
                'data' => $user
            ]);
        } else if ($user->role == 'Pembeli') {
            $user = User::with('pembeli')->find($user->id_user);
            return response()->json([
                'message' => 'Data user',
                'data' => $user
            ]);
        } else if ($user->role == 'Penitip') {
            $user = User::with('penitip')->find($user->id_user);
            return response()->json([
                'message' => 'Data user',
                'data' => $user
            ]);
        } else if ($user->role == 'Organisasi') {
            $user = User::with('organisasi')->find($user->id_user);
            return response()->json([
                'message' => 'Data user',
                'data' => $user
            ]);
        }

        return response()->json([
            'message' => 'Data user',
            'data' => $user
        ], 200);
    }

    public function updateAllPassword(Request $request)
    {
        $password = Hash::make($request->password);

        User::each(function ($user) use ($password) {
            $user->update([
                'password' => $password
            ]);
        });

        return response()->json([
            'message' => 'Password berhasil diubah'
        ]);
    }
}
