<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Keranjang;
use App\Models\DetailKeranjang;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Pembeli;
class PembayaranController
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
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'Pembeli') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk melihat total harga'
            ], 403);
        }

        $request->validate([
            'id_penjualan' => 'required|string|max:50',
            'metode_pembayaran' => 'required|in:BCA,BNI,Mandiri,BRI',
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
        ], [
            'id_penjualan.required' => 'ID Penjualan tidak boleh kosong',
            'metode_pembayaran.required' => 'Metode pembayaran tidak boleh kosong',
            'metode_pembayaran.in' => 'Metode pembayaran harus salah satu dari BCA, BNI, Mandiri, atau BRI',
            'bukti_pembayaran.required' => 'Bukti pembayaran tidak boleh kosong',
            'bukti_pembayaran.image' => 'Bukti pembayaran harus berupa gambar',
            'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berupa file dengan ekstensi jpeg, png, jpg, gif, svg, atau webp',
        ]);

        // ngecek apakah udah ada pembayaran dengan id yang sama
        if (Pembayaran::where('id_penjualan', $request->id_penjualan)->exists()) {
            return response()->json([
                'status_code' => 422,
                'message' => 'Penjualan sudah memiliki pembayaran',
                'errors' => ['id_penjualan' => 'Penjualan sudah memiliki pembayaran'],
            ], 422);
        }

        if (!$request->hasFile('bukti_pembayaran')) {
            return response()->json([
                'status_code' => 422,
                'message' => 'File tidak valid',
                'errors' => ['bukti_pembayaran' => 'File tidak valid'],
            ], 422);
        }

        // Simpan file ke storage/app/public/bukti_pembayaran
        $file = $request->file('bukti_pembayaran');
        $fileName = $request->id_penjualan . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('bukti_pembayaran', $fileName, 'public');

        $pembayaran = Pembayaran::create([
            'id_penjualan' => $request->id_penjualan,
            'tanggal_pembayaran' => now(),
            'metode_pembayaran' => $request->metode_pembayaran,
            'status_pembayaran' => 'Pending',
            'bukti_pembayaran' => $fileName // hanya simpan nama file
        ]);

        // aslinya komenku panjang, tapi andaikan aku butuh baca ulang, intinya disini:
        // mengambil keranjang pembeli, memasukkannya ke detail penjualan, ganti status produk, lalu ngehapus isi keranjang
        $pembeli = $user->pembeli;
        if ($pembeli) {
            // ambil keranjang saat ini
            $keranjang = Keranjang::where('id_pembeli', $pembeli->id_pembeli)->first();
            $id_keranjang = $keranjang->id_keranjang;
            $detailKeranjang = DetailKeranjang::with('produk')
                ->where('id_keranjang', $keranjang->id_keranjang)
                ->get();
            // ganti stok produk, masukkan ke detail penjualan
            $data = []; // ini untuk masukkin data keranjang
            foreach ($detailKeranjang as $detail) {
                $produk = $detail->produk;
                if ($produk) {
                    $data[] = [
                        'id_penjualan' => $request->id_penjualan,
                        'id_produk' => $produk->id_produk,
                    ];
                    $produk->status_ketersediaan = 0;
                    $produk->save(); // simpan perubahan
                }
            }
            // masukkan ke detail penjualan dan hapus keranjang
            DetailPenjualan::insert($data);
            DetailKeranjang::where('id_keranjang', $id_keranjang)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pembayaran berhasil dibuat',
            'data' => $pembayaran
        ], 201);
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

    // ini bagian si CS konfirmasi pembayaran
    public function konfirmasiPembayaran(string $id_penjualan ,Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'CS') {
            return response()->json([
                'errors' => 'Anda tidak memiliki izin',
                'message' => 'Anda tidak memiliki izin'
            ], 403);
        }

        // Mengecek apakah id_penjualan ada dalam database
        $pembayaran = Pembayaran::where('id_penjualan', $id_penjualan)->first();
        if (!$pembayaran) {
            return response()->json([
                'errors' => 'Pembayaran tidak ditemukan',
                'message' => 'Pembayaran tidak ditemukan'
            ], 404);
        }
        // Mengecek apakah pembayarannya masih pending
        if ($pembayaran->status_pembayaran !== 'Pending') {
            return response()->json([
                'errors' => 'Pembayaran sudah dikonfirmasi',
                'message' => 'Pembayaran sudah dikonfirmasi'
            ], 400);
        }
        // membuat statusnya menjadi Lunas
        $pembayaran->status_pembayaran = 'Lunas';
        $pembayaran->save();

        // membuat status_penjualan di tabel penjualan menjadi Diproses
        $penjualan = Penjualan::find($id_penjualan);
        $penjualan->status_penjualan = 'Diproses';
        $penjualan->save();

        // membuat setiap produk yang ada di detail penjualan menjadi status_penjualannya terjual
        $detailPenjualan = DetailPenjualan::where('id_penjualan', $id_penjualan)->get();
        foreach ($detailPenjualan as $detail) {
            $produk = Produk::find($detail->id_produk);
            $produk->status_akhir_produk = 'Terjual';
            $produk->save();
        }

        // FOR YOBEL : KAU TAMBAH LAH DISINI KOMISI, BISA LANGSUNG ATAU KAU JADIIN FUNGSI LALU PANGGIL CONTROLLER

        // end
        return response()->json([
            'status' => 'success',
            'message' => 'Pembayaran berhasil dikonfirmasi',
            'data' => $pembayaran
        ], 200);
    }

    // kebalikannya, menolak pembayaran, statusnya jadi Ditolak
    public function tolakPembayaran(string $id_penjualan ,Request $request)
    {
        // Mengecek user yang sedang login
        $user = $request->user();
        if ($user->role !== 'CS') {
            return response()->json([
                'errors' => 'Anda tidak memiliki izin',
                'message' => 'Anda tidak memiliki izin'
            ], 403);
        }

        // Mengecek apakah id_penjualan ada dalam database
        $pembayaran = Pembayaran::where('id_penjualan', $id_penjualan)->first();
        if (!$pembayaran) {
            return response()->json([
                'errors' => 'Pembayaran tidak ditemukan',
                'message' => 'Pembayaran tidak ditemukan'
            ], 404);
        }
        // Mengecek apakah pembayarannya masih pending
        if ($pembayaran->status_pembayaran !== 'Pending') {
            return response()->json([
                'errors' => 'Pembayaran sudah dikonfirmasi',
                'message' => 'Pembayaran sudah dikonfirmasi'
            ], 400);
        }
        // membuat statusnya menjadi Ditolak
        $pembayaran->status_pembayaran = 'Ditolak';
        $pembayaran->save();

        // tambahan logika, kalau ditolak, maka akan mengembalikan poin user serta membuat stok produk jadi 1 lagi
        // ambil poin yang mau dikembalikan dari penjualan.
        $penjualan = Penjualan::find($id_penjualan);
        $poin = $penjualan->poin_potongan - $penjualan->poin_perolehan;
        $pembeli = Pembeli::find($penjualan->id_pembeli);
        $pembeli->poin += $poin;
        $pembeli->save();

        // ambil semua produk yang batal dari detail penjualan
        $detail_penjualan = DetailPenjualan::where('id_penjualan', $id_penjualan)->get();
        foreach ($detail_penjualan as $detail) {
            $produk = Produk::find($detail->id_produk);
            $produk->status_ketersediaan = 1;
            $produk->save();
        }

        // Jika ada pengiriman, maka batalkan
        if ($penjualan->pengiriman) {
            $penjualan->pengiriman->status_pengiriman = 'Batal';
            $penjualan->pengiriman->save();
        }

        // end
        return response()->json([
            'status' => 'success',
            'message' => 'Pembayaran berhasil ditolak',
            'data' => $pembayaran
        ], 200);
    }
}
