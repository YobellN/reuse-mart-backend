<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Models\Produk;
use App\Services\FcmChannel;
use Illuminate\Http\Request;
use App\Models\RequestDonasi;
use Illuminate\Support\Facades\DB;

class DonasiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $donasi = Donasi::with(['requestDonasi.organisasi.user', 'produk.fotoProduk'])->get();

        return response()->json([
            'message' => 'Data Donasi',
            'data' => $donasi
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
        DB::beginTransaction();

        try {
            $request->validate([
                'id_request_donasi' => 'required|exists:request_donasi,id_request_donasi',
                'id_produk' => 'required|exists:produk,id_produk',
                'tanggal_donasi' => 'required|date',
                'nama_penerima' => 'required|string|min:3',
            ], [
                'id_request_donasi.required' => 'ID request donasi tidak boleh kosong',
                'id_request_donasi.exists' => 'ID request donasi tidak ditemukan',
                'id_produk.required' => 'ID produk tidak boleh kosong',
                'id_produk.exists' => 'ID produk tidak ditemukan',
                'tanggal_donasi.required' => 'Tanggal donasi tidak boleh kosong',
                'tanggal_donasi.date' => 'Tanggal donasi harus berupa tanggal',
                'nama_penerima.required' => 'Nama penerima tidak boleh kosong',
                'nama_penerima.min' => 'Nama penerima minimal 3 karakter',
            ]);

            $produk = Produk::find($request->id_produk);
            if (!$produk) {
                return response()->json([
                    'message' => 'Produk tidak ditemukan'
                ], 404);
            }
            $harga_produk = $produk->harga_produk;
            $total_poin = round($harga_produk / 10000);

            //store dulu data donasinya
            $donasi = Donasi::create([
                'id_request_donasi' => $request->id_request_donasi,
                'id_produk' => $request->id_produk,
                'tanggal_donasi' => $request->tanggal_donasi,
                'nama_penerima' => $request->nama_penerima,
                'total_poin' => $total_poin
            ]);

            //ini untuk update poin penitip
            $penitip = $produk->detailPenitipan->penitipan->penitip;
            if ($produk->detailPenitipan->konfirmasi_donasi == 1) {
                $penitip->increment('poin', $total_poin);
                $penitip->save();
            }

            //notif penitip
            $fcm_token = $penitip->user->fcm_token;
            if ($fcm_token) {
                $result = FcmChannel::send(
                    $fcm_token,
                    "🎁 Produk Didonasikan!",
                    "🎉 Produk *{$produk->nama_produk}* telah berhasil didonasikan ke lembaga sosial! Terima kasih telah berbagi kebaikan 💖",
                );
            }

            $request_donasi = RequestDonasi::find($request->id_request_donasi);
            if (!$request_donasi) {
                return response()->json([
                    'message' => 'Request donasi tidak ditemukan'
                ], 404);
            }
            $request_donasi->status_request = 1;
            $request_donasi->save(); //status req nya kita ganti

            $produk->status_akhir_produk = "Didonasikan";
            $produk->save(); //status produknya juga diganti


            DB::commit();

            return response()->json([
                'message' => 'Donasi berhasil ditambahkan',
                'data' => $donasi,
                'notifikasi' => $result ?? null,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function donasiPerTahun(string $tahun)
    {
        // Validasi: tahun harus 4 digit angka dan masuk akal
        if (!preg_match('/^\d{4}$/', $tahun) || $tahun < 2000 || $tahun > date('Y')) {
            return response()->json([
                'message' => 'Tahun tidak valid. Masukkan tahun 4 digit yang benar.',
            ], 422);
        }

        $donasi = Donasi::with([
            'requestDonasi.organisasi.user',
            'produk.detailPenitipan.penitipan.penitip.user'
        ])
            ->whereYear('tanggal_donasi', $tahun)
            ->get();

        $customDonasi = $donasi->map(function ($item) {
            return [
                'id_produk'       => $item->produk->id_produk, // kode produk
                'nama_produk'     => $item->produk->nama_produk,
                'id_penitip'      => $item->produk->detailPenitipan->penitipan->penitip->id_penitip,
                'nama_penitip'    => $item->produk->detailPenitipan->penitipan->penitip->user->nama,
                'tanggal_donasi'  => $item->tanggal_donasi,
                'nama_organisasi' => $item->requestDonasi->organisasi->user->nama,
                'nama_penerima'   => $item->nama_penerima,
            ];
        });

        return response()->json([
            'message' => 'Data Donasi per Tahun',
            'tahun' => $tahun,
            'data' =>  $customDonasi
        ], 200);
    }

    // Fungsi untuk mendapatkan tahun terlama donasi
    public function tahunTerlama()
    {
        $tahunTerlama = Donasi::min(DB::raw('YEAR(tanggal_donasi)'));

        if (!$tahunTerlama) {
            return response()->json([
                'message' => 'Tidak ada data donasi yang ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Tahun terlama donasi',
            'tahun' => $tahunTerlama,
        ], 200);
    }
}
