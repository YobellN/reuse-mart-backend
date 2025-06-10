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

    public function laporanPenjualanKotorBulanan(Request $request)
    {
        $tahun = $request->input('tahun') ?? date('Y');

        $data = DB::select("
            SELECT 
                MONTHNAME(pj.tanggal_penjualan) AS bulan,
                COUNT(dp.id_produk) AS jumlah_barang_terjual,
                SUM(pr.harga_produk) AS jumlah_penjualan_kotor
            FROM penjualan pj
            JOIN detail_penjualan dp ON pj.id_penjualan = dp.id_penjualan
            JOIN produk pr ON dp.id_produk = pr.id_produk
            WHERE pj.status_penjualan = 'Selesai'
              AND YEAR(pj.tanggal_penjualan) = ?
            GROUP BY MONTH(pj.tanggal_penjualan), MONTHNAME(pj.tanggal_penjualan)
            ORDER BY MONTH(pj.tanggal_penjualan)
        ", [$tahun]);

        return response()->json([
            'message' => 'Laporan Penjualan Kotor Bulanan',
            'data' => $data,
            'tahun' => $tahun
        ]);
    }

    public function laporanKomisiProduk(Request $request)
    {
        $tahun = $request->input('tahun') ?? date('Y');
        $bulan = $request->input('bulan') ?? date('m');

        $data = DB::select(
            "
            SELECT 
                pr.id_produk AS kodeProduk,
                pr.nama_produk AS namaProduk,
                CAST(pr.harga_produk AS FLOAT) AS hargaJual,
                DATE_FORMAT(pnt.tanggal_penitipan, '%Y-%m-%d') AS tanggalMasuk,
                DATE_FORMAT(pn.tanggal_penjualan, '%Y-%m-%d') AS tanggalLaku,
                COALESCE(CAST(k.komisi_hunter AS FLOAT), 0) AS komisiHunter,
                COALESCE(CAST(k.komisi_perusahaan AS FLOAT), 0) AS komisiReuseMart,
                COALESCE(CAST(k.bonus_penitip AS FLOAT), 0) AS bonusPenitip
            FROM komisi k
            JOIN detail_penjualan dp ON k.id_detail_penjualan = dp.id_detail_penjualan
            JOIN penjualan pn ON dp.id_penjualan = pn.id_penjualan
            JOIN produk pr ON dp.id_produk = pr.id_produk
            JOIN detail_penitipan dpt ON pr.id_produk = dpt.id_produk
            JOIN penitipan pnt ON dpt.id_penitipan = pnt.id_penitipan
            WHERE MONTH(pn.tanggal_penjualan) = ?
            AND YEAR(pn.tanggal_penjualan) = ?
            AND pn.status_penjualan = 'Selesai'
            ORDER BY pn.tanggal_penjualan
            ",
            [$bulan, $tahun]
        );

        return response()->json([
            'message' => 'Laporan Komisi Bulanan per Produk',
            'data' => $data
        ]);
    }

   public function laporanStokGudang()
    {
        $data = DB::select(
            "
            SELECT 
                pr.id_produk AS kodeProduk,
                pr.nama_produk AS namaProduk,
                p.id_penitip AS idPenitip,
                u.nama AS namaPenitip,
                DATE_FORMAT(pn.tanggal_penitipan, '%Y-%m-%d') AS tanggalMasuk,
                CASE
                    WHEN pn.status_perpanjangan = 1 THEN 'Ya'
                    ELSE 'Tidak'
                END AS perpanjangan,
                COALESCE(pn.id_hunter, 'Tidak ada hunter') AS idHunter,
                COALESCE(upg.nama, 'Tidak ada hunter') AS namaHunter,
                CAST(pr.harga_produk AS FLOAT) AS hargaProduk
            FROM produk pr
            JOIN detail_penitipan dpt ON pr.id_produk = dpt.id_produk
            JOIN penitipan pn ON dpt.id_penitipan = pn.id_penitipan
            LEFT JOIN pegawai pg ON pn.id_hunter = pg.id_pegawai
            LEFT JOIN user upg ON pg.id_user = upg.id_user
            JOIN penitip p ON p.id_penitip = pn.id_penitip
            JOIN user u ON p.id_user = u.id_user
            WHERE pr.status_akhir_produk IS NULL
            AND pn.tanggal_penitipan <= CURDATE()
            ORDER BY pn.tanggal_penitipan
            "
        );

        return response()->json([
            'message' => 'Laporan Stok Gudang Hari Ini',
            'data' => $data
        ]);
    }



}
