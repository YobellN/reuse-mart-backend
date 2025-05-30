<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Komisi;
use App\Models\Penjualan;
use App\Models\Pengiriman;
use App\Services\FcmChannel;
use Illuminate\Http\Request;
use App\Services\PenjualanService;
use Illuminate\Support\Facades\Log;

class PengirimanController
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
        $pengiriman = Pengiriman::with([
            'alamat',
            'kurir.jabatan',
            'kurir.user',
            'penjualan'
        ])->find($id);

        if (!$pengiriman) {
            return response()->json([
                'message' => 'Pengiriman tidak ditemukan',
                'errors' => ['id' => 'Pengiriman tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Detail Pengiriman',
            'data' => $pengiriman
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
        $pengiriman = Pengiriman::with([
            'alamat',
            'kurir',
            'penjualan'
        ])->find($id);

        $validated = $request->validate([
            'id_kurir' => 'sometimes|exists:pegawai,id_pegawai',
            'jadwal_pengiriman' => 'sometimes|date',
        ], [
            'id_kurir.exists' => 'Kurir tidak ditemukan',
            'jadwal_pengiriman.date' => 'Jadwal pengiriman tidak valid',
        ]);

        $jadwal = $request->jadwal_pengiriman ? Carbon::parse($request->jadwal_pengiriman) : null;

        if ($jadwal && $jadwal->isToday() && now()->greaterThan(now()->setHour(16)->setMinute(0))) {
            return response()->json([
                'message' => 'Jadwal pengiriman hari ini tidak dapat dipilih setelah pukul 16:00.',
            ], 422);
        }

        $pengiriman->update([
            'id_kurir' => $validated['id_kurir'],
            'jadwal_pengiriman' => $validated['jadwal_pengiriman'],
            'status_pengiriman' => 'Menunggu Kurir',
        ]);

        $pengiriman->penjualan()->update([
            'status_penjualan' => 'Dikirim'
        ]);

        $penjualan = Penjualan::with([
            'pembeli.user',
            'detail.produk.kategori',
            'detail.produk.fotoProduk',
            'pengiriman.alamat',
            'pembayaran',
        ])->find($pengiriman->id_penjualan);

        if (!$penjualan) {
            return response()->json([
                'message' => 'Penjualan tidak ditemukan',
                'errors' => ['id' => 'Penjualan tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Pengiriman berhasil dijadwalkan',
            'data' => $penjualan
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function jadwalkanPengambilan($id, Request $request)
    {
        $validated = $request->validate([
            'jadwal_pengambilan' => 'required|date',
        ]);

        $penjualan = Penjualan::with([
            'pembeli.user',
            'detail.produk.kategori',
            'detail.produk.fotoProduk',
            'pengiriman.alamat',
            'pembayaran',
        ])->find($id);

        if (!$penjualan) {
            return response()->json([
                'message' => 'Penjualan tidak ditemukan',
                'errors' => ['id' => 'Penjualan tidak ditemukan'],
            ], 404);
        }

        $penjualan->update([
            'jadwal_pengambilan' => $validated['jadwal_pengambilan'],
            'status_penjualan' => 'Menunggu Pengambilan',
        ]);

        return response()->json([
            'message' => 'Penjualan berhasil dijadwalkan',
            'data' => $penjualan
        ], 200);
    }

    public function konfirmasiPengambilanTransaksi($id)
    {
        $penjualan = Penjualan::with([
            'pembeli.user',
            'detail.produk.kategori',
            'detail.produk.fotoProduk',
            'pengiriman.alamat',
            'pembayaran',
        ])->find($id);

        if (!$penjualan) {
            return response()->json([
                'message' => 'Penjualan tidak ditemukan',
                'errors' => ['id' => 'Penjualan tidak ditemukan'],
            ], 404);
        }

        $penjualan->update([
            'status_penjualan' => 'Selesai',
        ]);

        $fcmPembeli = $penjualan->pembeli()->first()->user->fcm_token;
        $fcmPenitip = $penjualan->detail()->first()->produk->detailPenitipan()->first()->penitipan()->first()->penitip->user->fcm_token;

        if ($fcmPembeli) {
            $notifPembeli = FcmChannel::send(
                $fcmPembeli,
                'Transaksi Anda Berhasil!',
                'Terima kasih telah berbelanja di ReUse Mart. Barang Anda telah berhasil diterima. Sampai jumpa di transaksi berikutnya!'
            );
        }

        if ($fcmPenitip) {
            $notifPenitip = FcmChannel::send(
                $fcmPenitip,
                'Barang Anda Telah Terjual!',
                'Selamat! Barang titipan Anda telah berhasil terjual melalui ReUse Mart. Terima kasih telah mempercayakan kami.'
            );
        }
        return response()->json([
            'message' => 'Penjualan berhasil dikonfirmasi',
            'data' => $penjualan,
            'notifPembeli' => $notifPembeli,
            'notifPenitip' => $notifPenitip,
        ], 200);
    }
}
