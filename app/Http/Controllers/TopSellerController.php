<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use App\Models\TopSeller;
use App\Models\Produk;
use App\Models\Penitip;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class TopSellerController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $topSeller = TopSeller::with('penitip')->get();

        return response()->json([
            'message' => 'Data Top Seller',
            'data' => $topSeller
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
    public function store(Request $request, $id)
    {
        
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

    public function topSellerBulanLalu()
    {
        $lastMonth = Carbon::now()->subMonth();

        $topSeller = TopSeller::with('penitip', 'penitip.user')
            ->whereMonth('tanggal_mulai', $lastMonth->month)
            ->whereYear('tanggal_mulai', $lastMonth->year)
            ->get()
            ->map(function ($seller) {
                $penitip = $seller->penitip;

                $avgRating = Produk::join('detail_penitipan', 'produk.id_produk', '=', 'detail_penitipan.id_produk')
                    ->join('penitipan', 'detail_penitipan.id_penitipan', '=', 'penitipan.id_penitipan')
                    ->where('penitipan.id_penitip', $penitip->id_penitip)
                    ->avg('produk.rating');

                $totalProduk = Produk::join('detail_penitipan', 'produk.id_produk', '=', 'detail_penitipan.id_produk')
                    ->join('penitipan', 'detail_penitipan.id_penitipan', '=', 'penitipan.id_penitipan')
                    ->where('penitipan.id_penitip', $penitip->id_penitip)
                    ->count();

                return [
                    'id_top_seller' => $seller->id_top_seller,
                    'id_penitip' => $penitip->id_penitip,
                    'tanggal_mulai' => $seller->tanggal_mulai,
                    'tanggal_selesai' => $seller->tanggal_selesai,
                    'total_penjualan' => $seller->total_penjualan,
                    'bonus' => $seller->bonus,
                    'avg_rating' => $avgRating ? round((float) $avgRating, 2) : null,
                    'total_produk' => $totalProduk,
                    'penitip' => $penitip,
                ];
            });

        return response()->json([
            'message' => 'Top Seller bulan lalu',
            'data' => $topSeller,
        ], 200);
    }

    public function generateTopSellerBulanLalu()
    {
        try {

            $lastMonth = Carbon::now()->subMonth();
            $bulan = $lastMonth->month;
            $tahun = $lastMonth->year;

            // CEK APAKAH UDAH ADA TOP SELLER BULAN LALU ATO BLOM
            $existing = TopSeller::whereMonth('tanggal_mulai', $bulan)
                ->whereYear('tanggal_mulai', $tahun)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Top Seller bulan lalu sudah ditentukan. Tidak dapat dikirim ulang.',
                    'data' => $existing->load('penitip.user')
                ], 200);
            }

            // HITUNG TOTAL PENJUALAN SELESAI BULAN LALU SEMUA PENITIP
            $topSeller = DB::table('penitip as p')
                ->join('penitipan as pt', 'p.id_penitip', '=', 'pt.id_penitip')
                ->join('detail_penitipan as dpt', 'pt.id_penitipan', '=', 'dpt.id_penitipan')
                ->join('produk as pr', 'dpt.id_produk', '=', 'pr.id_produk')
                ->join('detail_penjualan as dpj', 'pr.id_produk', '=', 'dpj.id_produk')
                ->join('penjualan as pj', 'dpj.id_penjualan', '=', 'pj.id_penjualan')
                ->where('pj.status_penjualan', 'Selesai')
                ->whereMonth('pj.tanggal_penjualan', $bulan)
                ->whereYear('pj.tanggal_penjualan', $tahun)
                ->select('p.id_penitip', DB::raw('SUM(pr.harga_produk) as total_penjualan'))
                ->groupBy('p.id_penitip')
                ->orderByDesc('total_penjualan')
                ->limit(1)
                ->first();

            if (!$topSeller) {
                return response()->json([
                    'message' => 'Tidak ada penjualan selesai bulan lalu. Tidak bisa menentukan Top Seller.',
                ], 404);
            }

            $bonus = (int) floor($topSeller->total_penjualan * 0.01);

            // SIMPAN TOP SELLER
            $topSellerModel = TopSeller::create([
                'id_penitip' => $topSeller->id_penitip,
                'tanggal_mulai' => Carbon::create($tahun, $bulan, 1)->startOfMonth(),
                'tanggal_selesai' => Carbon::create($tahun, $bulan, 1)->endOfMonth(),
                'total_penjualan' => (int) $topSeller->total_penjualan,
                'bonus' => (int) $bonus,
            ]);

            // BONUS POOIN DAN SALDO
            Penitip::where('id_penitip', $topSeller->id_penitip)->incrementEach([
                'poin' => $bonus,
                'saldo' => $bonus
            ]);

            return response()->json([
                'message' => 'Top Seller bulan lalu berhasil disimpan dan diberikan bonus poin & saldo.',
                'data' => $topSellerModel->load('penitip.user'),
            ]);

        } catch (\Exception $e) {
        Log::error('Gagal generate top seller: ' . $e->getMessage());
        return response()->json([
            'message' => 'Gagal memuat data Top Seller.',
            'data' => null,
        ], 500);
    }
    }
}
