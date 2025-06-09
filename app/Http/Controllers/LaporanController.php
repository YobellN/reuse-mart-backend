<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class LaporanController
{
    public function laporanPenjualanKategori(Request $request)
    {
        $tahun = $request->input('tahun') ?? date('Y');

        $data = DB::select("
                SELECT 
                    k.nama_kategori,
                    SUM(CASE 
                            WHEN p.status_akhir_produk = 'Terjual' 
                                AND YEAR(pj.tanggal_penjualan) = ?
                                AND YEAR(pt.tanggal_penitipan) = ? 
                            THEN 1 ELSE 0 
                        END) AS jumlah_item_terjual,

                    SUM(CASE 
                            WHEN p.status_akhir_produk IS NOT NULL 
                                AND p.status_akhir_produk <> 'Terjual'
                                AND YEAR(pt.tanggal_penitipan) = ?
                            THEN 1 ELSE 0 
                        END) AS jumlah_item_gagal_terjual
                FROM kategori_produk k
                JOIN produk p ON k.id_kategori = p.id_kategori
                LEFT JOIN detail_penjualan dp ON p.id_produk = dp.id_produk
                LEFT JOIN penjualan pj ON dp.id_penjualan = pj.id_penjualan
                LEFT JOIN detail_penitipan dpt ON p.id_produk = dpt.id_produk
                LEFT JOIN penitipan pt ON dpt.id_penitipan = pt.id_penitipan
                GROUP BY k.nama_kategori
            ", [$tahun, $tahun, $tahun]);

        return response()->json([
            'message' => 'Laporan Penjualan Kategori',
            'data' => $data
        ]);
    }

    public function laporanBarangHangus(Request $request)
    {
        $tahun = $request->input('tahun') ?? date('Y');
        $bulan = $request->input('bulan') ?? date('m');

        $data = DB::select(
            "
            SELECT 
                produk.id_produk,
                produk.nama_produk,
                p.id_penitip,
                u.nama AS nama_penitip,
                pt.tanggal_penitipan,
                pt.tenggat_penitipan,
                pt.tenggat_pengambilan
            FROM produk
            JOIN detail_penitipan dp ON produk.id_produk = dp.id_produk
            JOIN penitipan pt ON dp.id_penitipan = pt.id_penitipan
            JOIN penitip p ON pt.id_penitip = p.id_penitip
            JOIN user u ON p.id_user = u.id_user
            WHERE YEAR(pt.tenggat_penitipan) = ?
            AND MONTH(pt.tenggat_penitipan) = ?
            AND pt.tenggat_penitipan < NOW()
            AND (
                produk.status_akhir_produk = 'Tidak Laku')",
            [$tahun, $bulan]
        );

        // $data = DB::select(
        //     "
        //     SELECT 
        //         produk.id_produk,
        //         produk.nama_produk,
        //         p.id_penitip,
        //         u.nama AS nama_penitip,
        //         pt.tanggal_penitipan,
        //         pt.tenggat_penitipan,
        //         pt.tenggat_pengambilan
        //     FROM produk
        //     JOIN detail_penitipan dp ON produk.id_produk = dp.id_produk
        //     JOIN penitipan pt ON dp.id_penitipan = pt.id_penitipan
        //     JOIN penitip p ON pt.id_penitip = p.id_penitip
        //     JOIN user u ON p.id_user = u.id_user
        //     WHERE YEAR(pt.tenggat_penitipan) = ?
        //     AND MONTH(pt.tenggat_penitipan) = ?
        //     AND pt.tenggat_penitipan < NOW()",
        //     [$tahun, $bulan]
        // );

        return response()->json([
            'message' => 'Laporan Barang Hangus',
            'data' => $data
        ]);
    }
}
