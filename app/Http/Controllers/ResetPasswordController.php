<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ResetPasswordController
{
    public function sendResetLink(Request $request)
    {
        // Validasi email
        $request->validate(['email' => 'required|email']);

        // Cek user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan',
                'errors' => ['email' => 'Email tidak terdaftar'],
            ], 404);
        }

        // Generate token
        $token = Str::random(64);

        // Simpan token ke database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Generate reset URL
        $resetUrl = config('app.frontend_url') . "/reset-password?token=" . $token . "&email=" . urlencode($request->email);

        // Kirim email dengan link
        Mail::send('emails.reset-password', ['resetUrl' => $resetUrl], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Password Notification');
        });

        return response()->json([
            'message' => 'Link reset password telah dikirim ke email Anda'
        ], 200);
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email'
        ]);

        $resetData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetData) {
            return response()->json(data: [
                'message' => 'Invalid token!'
            ], status: 400);
        }

        // Cek token expired (15 menit)
        $created = Carbon::parse($resetData->created_at);
        if (Carbon::now()->diffInMinutes($created) > 15) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Token telah expired!'
            ], status: 400);
        }

        return response()->json(data: [
            'message' => 'Token valid',
            'email' => $request->email
        ], status: 200);
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|min:8',
        ]);
        // ngecek password sama
        if ($request->password !== $request->confirm_password) {
            return response()->json([
                'message' => 'Password tidak sama',
                'errors' => ['password' => 'Password tidak sama'],
            ], 422);
        }

        // Update password
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Hapus token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(data: [
            'message' => 'Password berhasil direset!'
        ], status: 200);
    }

    public function resetPasswordPegawai(Request $request)
    {
        // Validasi input
        $request->validate([
            'id_pegawai' => 'required|string',
        ]);

        // Cari pegawai dan user terkait
        $pegawai = Pegawai::with('user')->find($request->id_pegawai);

        if (!$pegawai) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
                'errors' => ['id' => 'Pegawai tidak ditemukan'],
            ], 404);
        }

        if (!$pegawai->user) {
            return response()->json([
                'message' => 'User tidak ditemukan untuk pegawai ini',
                'errors' => ['user' => 'User tidak ditemukan'],
            ], 404);
        }

        // Format tanggal lahir untuk password
        $tgl_lahir = Carbon::parse($pegawai->tanggal_lahir)->format('dmY');

        // Update password di tabel user
        $pegawai->user->update([
            'password' => Hash::make($tgl_lahir)
            // 'password' => Hash::make('12345')
        ]);

        return response()->json([
            'message' => 'Password pegawai berhasil direset menjadi tanggal lahir'
        ], 200);
    }
}
