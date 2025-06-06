<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Penjualan;
use App\Models\Pengiriman;
use Illuminate\Http\Request;
use App\Services\FcmChannel;


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
            'pengiriman.kurir.user',
            'pembayaran',
        ])->find($pengiriman->id_penjualan);

        if (!$penjualan) {
            return response()->json([
                'message' => 'Penjualan tidak ditemukan',
                'errors' => ['id' => 'Penjualan tidak ditemukan'],
            ], 404);
        }

        // disini notif

        $jadwalString = Carbon::parse($validated['jadwal_pengiriman'])->locale('id')->isoFormat('dddd, D MMMM YYYY [pukul] HH:mm');

        // Intinya ngambil semua id penitip, unik. Biar gak ngespam.
        $penitipUsers = collect($penjualan->detail)
            ->map(fn($d) => $d->produk->detailPenitipan->first()?->penitipan->penitip->user)
            ->filter() // hapus null
            ->unique('id') // hilangkan user duplikat
            ->values();

        foreach ($penitipUsers as $user) {
            if ($user->fcm_token) {
                FcmChannel::send(
                    $user->fcm_token,
                    'Barang Titipan Anda Akan Dikirim',
                    "Produk yang Anda titipkan akan dikirim ke pembeli pada $jadwalString. Terima kasih telah menggunakan ReUse Mart."
                );
            }
        }

        // Notifikasi ke pembeli seperti biasa
        $fcmPembeli = $penjualan->pembeli->user->fcm_token ?? null;
        if ($fcmPembeli) {
            FcmChannel::send(
                $fcmPembeli,
                'Pesanan Anda Akan Dikirim',
                "Barang yang Anda beli akan dikirimkan ke alamat Anda pada $jadwalString. Terima kasih telah berbelanja di ReUse Mart!"
            );
        }

        // Notifikasi ke kurir
        $fcmKurir = $pengiriman->kurir->user->fcm_token ?? null;
        if ($fcmKurir) {
            FcmChannel::send(
                $fcmKurir,
                'Tugas Pengiriman Baru',
                "Anda memiliki tugas pengiriman pada $jadwalString. Silakan cek aplikasi ReUse Mart untuk detail pengiriman."
            );
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
            'pengiriman.kurir.user',
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

        $jadwal = Carbon::parse($validated['jadwal_pengambilan'])->locale('id')->isoFormat('dddd, D MMMM YYYY [pukul] HH:mm');

        // Intinya ngambil semua id penitip, unik. Biar gak ngespam.
        $penitipUsers = collect($penjualan->detail)
            ->map(fn($d) => $d->produk->detailPenitipan->first()?->penitipan->penitip->user)
            ->filter() // hapus null
            ->unique('id') // hilangkan user duplikat
            ->values();

        foreach ($penitipUsers as $user) {
            if ($user->fcm_token) {
                FcmChannel::send(
                    $user->fcm_token,
                    'Barang Titipan Anda Akan Dikirim',
                    "Produk yang Anda titipkan akan dikirim ke pembeli pada $jadwal. Terima kasih telah menggunakan ReUse Mart."
                );
            }
        }

        // Notifikasi ke pembeli seperti biasa
        $fcmPembeli = $penjualan->pembeli->user->fcm_token ?? null;
        if ($fcmPembeli) {
            FcmChannel::send(
                $fcmPembeli,
                'Jadwal Pengambilan Barang',
                "Barang yang Anda beli bisa diambil pada $jadwal. Terima kasih telah berbelanja di ReUse Mart!"
            );
        }

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
            'pengiriman.kurir.user',
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
        if ($fcmPembeli) {
            $notifPembeli = FcmChannel::send(
                $fcmPembeli,
                '✅ Transaksi Berhasil!',
                '🎉 Terima kasih telah berbelanja di ReUse Mart! Barang Anda sudah diterima. Sampai jumpa di transaksi berikutnya 🛍️'
            );
        }

        $penitipFcmTokens = [];
        $notifPenitip = [];

        foreach ($penjualan->detail as $detail) {
            $produk = $detail->produk;
            if (!$produk) continue;

            $detailPenitipan = $produk->detailPenitipan()->first();
            if (!$detailPenitipan) continue;

            $penitipan = $detailPenitipan->penitipan()->first();
            if (!$penitipan) continue;

            $penitipUser = $penitipan->penitip->user ?? null;
            if (!$penitipUser) continue;

            $fcmPenitip = $penitipUser->fcm_token;

            if ($fcmPenitip && !in_array($fcmPenitip, $penitipFcmTokens)) {
                $notifPenitip[] = FcmChannel::send(
                    $fcmPenitip,
                    '📦 Barang Anda Telah Terjual!',
                    '🎊 Selamat! Barang titipan Anda sudah laku di ReUse Mart. Terima kasih telah mempercayakan kami untuk menjualnya 🙌'
                );

                $penitipFcmTokens[] = $fcmPenitip;
            }
        }

        return response()->json([
            'message' => 'Penjualan berhasil dikonfirmasi',
            'data' => $penjualan,
            'notifPembeli' => $notifPembeli,
            'notifPenitip' => $notifPenitip,
        ], 200);
    }

    // aku gk nampak fungsionalitas untuk nomor 2 yang td, jadinya gk ada yang 'Diambil oleh kurir' untuk pengiriman.
    // aku bikin di backend sebiji, public function dikirim Kurir(Request $request, string $id), ada di controller pengiriman. 
    // kalau dipanggil dan masang id_penjualannya, dia ngubah pengiriman jadi diambil kurir. lalu ngasih notif.
    public function dikirimKurir(Request $request, string $id)
    {
        $pengiriman = Pengiriman::with([
            'kurir',
            'penjualan'
        ])->find($id);

        // ngecek apakah sudah ada id kurir
        if (!$pengiriman->id_kurir) {
            return response()->json([
                'message' => 'Kurir belum diisi',
                'errors' => ['id' => 'Kurir belum diisi'],
            ]);
        }

        // ngecek, apakah kurirnya sama
        $id_kurir = $request->user()->pegawai()->first()->id_pegawai;

        if ($id_kurir != $pengiriman->id_kurir) {
            return response()->json([
                'message' => 'Kurir tidak sesuai',
                'errors' => ['id' => 'Kurir tidak sesuai'],
            ]);
        }


        $pengiriman->update([
            'status_pengiriman' => 'Diambil oleh kurir',
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

        // disini notif
        // Intinya ngambil semua id penitip, unik. Biar gak ngespam.
        $penitipUsers = collect($penjualan->detail)
            ->map(fn($d) => $d->produk->detailPenitipan->first()?->penitipan->penitip->user)
            ->filter() // hapus null
            ->unique('id') // hilangkan user duplikat
            ->values();

        foreach ($penitipUsers as $user) {
            if ($user->fcm_token) {
                FcmChannel::send(
                    $user->fcm_token,
                    'Barang Titipan Anda telah diambil oleh kurir',
                    "Produk yang Anda titipkan sedang dikirim ke pembeli. Terima kasih telah menggunakan ReUse Mart."
                );
            }
        }

        // Notifikasi ke pembeli seperti biasa
        $fcmPembeli = $penjualan->pembeli->user->fcm_token ?? null;
        if ($fcmPembeli) {
            FcmChannel::send(
                $fcmPembeli,
                'Pesanan Anda sedang Dikirim',
                "Barang yang Anda beli sedang dalam perjalanan. Terima kasih telah berbelanja di ReUse Mart!"
            );
        }

        return response()->json([
            'message' => 'Pengiriman berhasil dijadwalkan',
            'data' => $penjualan
        ], 200);
    }
}
