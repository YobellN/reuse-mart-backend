<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Komisi;
use App\Models\Produk;
use App\Models\Penitip;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\DetailPenjualan;
use App\Services\PenjualanService;
use App\Http\Controllers\DetailKeranjangController;
use Illuminate\Support\Facades\Log;

class PenjualanController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $user = $request->user();
        $status_penjualan = $request->query('status_penjualan');

        if ($user->role === 'Pembeli') {
            $id_pembeli = $user->pembeli->id_pembeli;
            $penjualan = Penjualan::with([
                'pembeli.user',
                'detail.produk.kategori',
                'detail.produk.fotoProduk',
                'pengiriman.alamat',
                'pengiriman.kurir.user',
                'pembayaran',
            ])->where('id_pembeli', $id_pembeli)->when($status_penjualan, fn($q) => $q->where('status_penjualan', $status_penjualan))->orderBy('tanggal_penjualan', 'desc')->get();

            if ($penjualan->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data penjualan',
                ], 404);
            }

            return response()->json([
                'message' => 'Riwayat Penjualan',
                'data' => $penjualan,
            ]);
        } else if ($user->role === 'Gudang') {
            $metode_pengiriman = $request->query('metode_pengiriman');

            $penjualan = Penjualan::with([
                'pembeli.user',
                'detail.produk.kategori',
                'detail.produk.fotoProduk',
                'pengiriman.alamat',
                'pengiriman.kurir.user',
                'pembayaran',
            ])
                ->when($metode_pengiriman, function ($query) use ($metode_pengiriman) {
                    return $query->where('metode_pengiriman', $metode_pengiriman);
                })
                ->whereHas('pembayaran', function ($query) {
                    $query->where('status_pembayaran', 'Lunas');
                })
                ->when($status_penjualan, function ($query) use ($status_penjualan) {
                    return $status_penjualan === 'Selesai'
                        ? $query->whereIn('status_penjualan', ['Selesai', 'Dikirim', 'Hangus', 'Menunggu Pengambilan'])
                        : $query->where('status_penjualan', $status_penjualan);
                })
                ->orderBy('tanggal_penjualan', 'desc')
                ->get();


            if ($penjualan->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data penjualan',
                ], 404);
            }

            return response()->json([
                'message' => 'Riwayat Penjualan',
                'data' => $penjualan,
            ]);
        } else {
            return response()->json([
                'message' => 'Tidak memiliki akses',
            ], 404);
        }
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
            'metode_pengiriman' => 'required|in:Ambil di gudang,Antar Kurir',
            'poin_potongan' => 'nullable|integer|min:0',
            'id_alamat' => 'nullable|exists:alamat,id_alamat',
        ]);

        // Ambil user login
        $user = $request->user();
        if (!$user || $user->role !== 'Pembeli') {
            return response()->json(['message' => 'Hanya pembeli yang dapat melakukan penjualan'], 403);
        }

        $pembeli = $user->pembeli;
        if (!$pembeli) {
            return response()->json(['message' => 'Data pembeli tidak ditemukan'], 404);
        }

        // Panggil controller DetailKeranjangController@getTotalHarga
        $detailKeranjangController = new DetailKeranjangController();

        // Simulasi Request baru agar bisa digunakan oleh fungsi getTotalHarga
        $requestData = new Request([
            'poinKepakai' => $request->poin_potongan ?? 0,
            'metode_pengambilan' => $request->metode_pengiriman,
        ]);

        $requestData->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mengecek Apakah semua barang dikeranjang masih ada stok
        $masihAdaStok = $detailKeranjangController->cekStok($requestData);
        $masihAdaStokData = $masihAdaStok->getData();
        if ($masihAdaStokData->status !== 'success') {
            return response()->json(['message' => 'Maaf! Barang dalam keranjang Anda sudah dibeli.'], 500);
        }
        // Jalankan fungsi getTotalHarga
        $response = $detailKeranjangController->getTotalHarga($requestData);
        $responseData = $response->getData();

        if ($responseData->status !== 'success') {
            return response()->json(['message' => 'Gagal menghitung harga'], 500);
        }

        $harga = $responseData->data;

        // Simpan data penjualan
        $penjualan = Penjualan::create([
            'id_pembeli' => $pembeli->id_pembeli,
            'tanggal_penjualan' => now(),
            'metode_pengiriman' => $request->metode_pengiriman,
            'jadwal_pengambilan' => null,
            'total_ongkir' => $harga->ongkir,
            'poin_potongan' => $harga->poin_dipakai,
            'total_harga' => $harga->total_akhir,
            'poin_perolehan' => $harga->poin,
            'total_poin' => $pembeli->poin - $harga->poin_dipakai + $harga->poin, // Poin saat ini - poin yang dipakai + poin yang didapat
            'status_penjualan' => 'Menunggu Pembayaran',
            'tenggat_pembayaran' => now()->addMinutes(1), // ubah disini untuk tenggat pembayaran
        ]);

        // Mengurangi poin pembeli ketika udah membeli, untuk nambah poin bonus dilakukan ketika sudah konfirmasi pembayaran
        $pembeli->poin = $pembeli->poin - $harga->poin_dipakai;
        $pembeli->save();

        // membuat pengiriman jika metode pengiriman adalah "Antar Kurir"
        if ($request->metode_pengiriman === 'Antar Kurir') {
            $penjualan->pengiriman()->create([
                'id_kurir' => null, // Kurir akan ditentukan kemudian
                'id_alamat' => $request->id_alamat ?? $pembeli->alamat_utama->id_alamat,
                'jadwal_pengiriman' => null,
                'status_pengiriman' => 'Disiapkan',
            ]);
        }

        return response()->json([
            'message' => 'Penjualan berhasil dibuat',
            'data' => $penjualan,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

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

    // Mengambil seluruh data penjualan berdasarkan user yang terautentikasi
    public function getDetailPenjualanByPenitip(Request $request)
    {
        $user = $request->user();
        $penitip = Penitip::with('user')->where('id_user', $user->id_user)->first();

        $request->validate([
            'status' => 'nullable|in:Menunggu Pembayaran,Diproses,Disiapkan,Dikirim,Selesai,Batal,Hangus'
        ], [
            'status.in' => 'Status tidak valid'
        ]);

        if (!$penitip) {
            return response()->json([
                'message' => 'Akun bukan penitip',
            ], 404);
        }


        $details = DetailPenjualan::with(['penjualan', 'produk', 'komisi'])
            ->whereHas('komisi', function ($query) use ($penitip) {
                $query->where('id_penitip', $penitip->id_penitip);
            })
            ->whereHas('penjualan', function ($query) use ($request) {
                if ($request->status) {
                    $query->where('status_penjualan', $request->status);
                }
            })
            ->get()
            ->map(function ($detail) {
                return [
                    'id_penjualan' => $detail->penjualan->id_penjualan,
                    'tanggal_penjualan' => $detail->penjualan->tanggal_penjualan,
                    'id_pembeli' => $detail->penjualan->id_pembeli,
                    'komisi_penitip' => $detail->komisi->komisi_penitip,
                    'bonus_penitip' => $detail->komisi->bonus_penitip,
                    'id_produk' => $detail->produk->id_produk,
                    'nama_produk' => $detail->produk->nama_produk
                ];
            });

        if ($details->isEmpty()) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Data detail penjualan berdasarkan user',
            'data' => $details
        ], 200);
    }

    // mengambil total harga memakai id penjualan (25.05.0005 misalnya)
    public function getTagihanPembayaran(string $id, Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'errors' => 'Anda tidak memiliki izin untuk melihat tagihan pembayaran',
                'message' => 'Anda tidak memiliki izin untuk melihat tagihan pembayaran'
            ], 403);
        }

        $pembeli = $user->pembeli;
        if (!$pembeli) {
            return response()->json([
                'errors' => 'pembeli tidak ditemukan',
                'message' => 'Pembeli tidak ditemukan'
            ], 404);
        }

        // mengambil penjualan yang id nya sesuai request
        $penjualan = Penjualan::where('id_penjualan', $id)->first();
        if (!$penjualan) {
            return response()->json([
                'errors' => 'Penjualan tidak ditemukan',
                'message' => 'Penjualan tidak ditemukan'
            ], 404);
        }

        // mereturn total_harga dari penjualan tersebut
        return response()->json([
            'status' => 'success',
            'message' => 'Tagihan pembayaran',
            'data' => $penjualan->total_harga
        ], 200);
    }

    // buat tes saja
    public function tesKomisi($id)
    {
        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $hasil = PenjualanService::hitungKomisi($produk);

        return response()->json([
            'message' => 'Hasil komisi',
            'data' => $hasil
        ], 200);
    }

    // jgn dipake
    public function updateAllKomisi()
    {
        $penjualan = Penjualan::all();
        $hasilKomisi = [];

        foreach ($penjualan as $penj) {
            if ($penj->status_penjualan !== 'Selesai') {
                continue;
            }

            $detail = $penj->detail()->first();

            if (!$detail || !$detail->id_produk) {
                continue;
            }

            $produk = Produk::find($detail->id_produk);
            if (!$produk) {
                continue;
            }

            $komisi = PenjualanService::hitungKomisi($produk);

            $hasilKomisi[] = [
                'penjualan_id' => $penj->id,
                'produk' => $produk->nama_produk ?? 'Tidak ditemukan',
                'komisi' => $komisi,
            ];
        }

        return response()->json([
            'message' => 'Hasil komisi semua penjualan',
            'data' => $hasilKomisi
        ]);
    }

    //buat tes jg
    public function tesTambahSaldo()
    {
        $komisi = Komisi::all();
        $hasil = [];

        foreach ($komisi as $k) {
            $hasil[] = PenjualanService::tambahSaldo($k);
        }

        return response()->json([
            'message' => 'Hasil tambah saldo',
            'data' => $hasil
        ], 200);
    }

    //buat tes jg
    public function tesTambahPoin()
    {
        $penjualan = Penjualan::where('id_penjualan', '25.01.0001')->first();

        if (!$penjualan) {
            return response()->json([
                'message' => 'Penjualan tidak ditemukan',
            ], 404);
        }

        $hasil = PenjualanService::tambahPoin($penjualan);

        return response()->json([
            'message' => 'Hasil tambah poin',
            'data' => $hasil
        ], 200);
    }

    public function tambahPoinSaldo($id)
    {
        $penjualan = Penjualan::where('id_penjualan', $id)->first();

        if (!$penjualan) {
            return response()->json([
                'message' => 'Penjualan tidak ditemukan',
            ], 404);
        }

        $poin = PenjualanService::tambahPoin($penjualan);

        $detail_penjualan = $penjualan->detail()->get();

        foreach ($detail_penjualan as $detail) {
            $produk = $detail->produk;
            $komisi = PenjualanService::hitungKomisi($produk);
        }

        return response()->json([
            'message' => 'Hasil tambah poin',
            'poin' => $poin,
            'komisi' => $komisi
        ], 200);
    }
}
