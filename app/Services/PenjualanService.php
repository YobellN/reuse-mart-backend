<?php

namespace App\Services;

use App\Models\Komisi;
use App\Models\Penjualan;
use App\Models\Produk;
use Carbon\Carbon;

class PenjualanService
{
    public static function hitungKomisi(Produk $produk)
    {
        $presentaseKomisiPerusahaan = 0.2; // default
        $presentaseBonusPenitip = 0.1;
        $presentaseKomisiHunter = 0.05;

        $komisiHunter = 0;
        $bonusPenitip = 0;
        $terjual_cepat = 0;

        $detailPenjualan = $produk->detailPenjualan()->first();
        if (!$detailPenjualan) return 'Produk tidak memiliki penjualan';

        $cekDuplikat = Komisi::where('id_detail_penjualan', $detailPenjualan->id_detail_penjualan)->first();
        if ($cekDuplikat) {
            return 'Komisi untuk penjualan ini sudah pernah dihitung.';
        }

        $detailPenitipan = $produk->detailPenitipan()->first();
        if (!$detailPenitipan) return 'Produk belum memiliki detail penitipan';

        $penitipan = $detailPenitipan->penitipan()->first();
        if (!$penitipan) return 'Data penitipan tidak ditemukan';

        $penjualan = $detailPenjualan->penjualan()->first();
        if (!$penjualan || $penjualan->status_penjualan !== 'Selesai' || $penjualan->status_penjualan !== 'Hangus') {
            return 'Penjualan belum selesai';
        }

        $hunter = $penitipan->hunter()->first();

        if ($penitipan->status_perpanjangan == 1) {
            $presentaseKomisiPerusahaan = 0.3;
        }

        $hargaJual = $produk->harga_produk;
        $totalKomisi = $hargaJual * $presentaseKomisiPerusahaan;

        // Hitung bonus penitip jika terjual < 7 hari
        if (Carbon::parse($penitipan->tanggal_penitipan)->addDays(7)->gt($penjualan->tanggal_penjualan)) {
            $terjual_cepat = 1;
            $bonusPenitip = $totalKomisi * $presentaseBonusPenitip;
        }

        // Hitung komisi hunter (jika ada)
        if ($hunter) {
            $komisiHunter = $hargaJual * $presentaseKomisiHunter;
        }

        // Hitung komisi perusahaan akhir
        $komisiPerusahaan = $totalKomisi - $bonusPenitip - $komisiHunter;

        // Komisi penitip = sisa harga jual
        $komisiPenitip = $hargaJual - $totalKomisi;

        // Simpan ke database
        $komisi = Komisi::create([
            'id_detail_penjualan' => $detailPenjualan->id_detail_penjualan,
            'id_penitip' => $penitipan->id_penitip,
            'id_hunter' => $hunter->id_pegawai ?? null,
            'harga_jual' => $hargaJual,
            'komisi_perusahaan' => $komisiPerusahaan,
            'komisi_penitip' => $komisiPenitip,
            'komisi_hunter' => $komisiHunter,
            'bonus_penitip' => $bonusPenitip
        ]);

        return $komisi;
        // return [
        //     'harga_produk' => $hargaJual,
        //     'id_hunter' => $hunter->id_pegawai ?? null,
        //     'id_penitip' => $penitipan->id_penitip ?? null,
        //     'terjual_cepat' => $terjual_cepat,
        //     'presentase_komisi_perusahaan' => $presentaseKomisiPerusahaan,
        //     'komisi_penitip' => $komisiPenitip,
        //     'total_komisi' => $totalKomisi,
        //     'komisi_perusahaan' => $komisiPerusahaan,
        //     'bonus_penitip' => $bonusPenitip,
        //     'komisi_hunter' => $komisiHunter,
        //     'komisi' => $komisi ?? null
        // ];
    }

    public static function tambahSaldo(Komisi $komisi)
    {
        if ($komisi->ditambahkan == 1) {
            return [
                'message' => 'Komisi sudah ditambahkan sebelumnya',
                'komisi_id' => $komisi->id
            ];
        }

        $penitip = $komisi->penitip()->first();
        if (!$penitip) {
            return ['error' => 'Penitip tidak ditemukan'];
        }

        $hunter = $komisi->hunter()->first();

        $bonusPenitip = $komisi->bonus_penitip ?? 0;
        $komisiHunter = $komisi->komisi_hunter ?? 0;

        $saldoPenitipAwal = $penitip->saldo;
        $saldoPenitipAkhir = $saldoPenitipAwal + $komisi->komisi_penitip + $bonusPenitip;

        $penitip->update(['saldo' => $saldoPenitipAkhir]);

        $saldoHunterAwal = 0;
        $saldoHunterAkhir = 0;

        // update saldo hunter
        if ($hunter) {
            $saldoHunterAwal = $hunter->total_komisi ?? 0;
            $saldoHunterAkhir = $saldoHunterAwal + $komisiHunter;

            $hunter->update(['total_komisi' => $saldoHunterAkhir]);
        }

        //update status komisi sudah ditambahkan
        $komisi->update(['ditambahkan' => 1]);

        return [
            'message' => 'Saldo berhasil diperbarui',
            'komisi_id' => $komisi->id,
            'saldo_penitip_awal' => $saldoPenitipAwal,
            'saldo_penitip_akhir' => $saldoPenitipAkhir,
            'bonus_penitip' => $bonusPenitip,
            'saldo_hunter_awal' => $saldoHunterAwal,
            'saldo_hunter_akhir' => $saldoHunterAkhir,
            'komisi' => $komisi
        ];
    }

    public static function tambahPoin(Penjualan $penjualan) {
        if($penjualan->status_penjualan !== 'Selesai' || !$penjualan->status_penjualan !== 'Hangus') {
            return ['error' => 'Penjualan belum selesai'];
        }

        $pembeli = $penjualan->pembeli()->first();

        if(!$pembeli) {
            return ['error' => 'Pembeli tidak ditemukan'];
        }

        $poinAwal = $pembeli->poin;
        $poinAkhir = $poinAwal + $penjualan->poin_perolehan;

        $pembeli->update(['poin' => $poinAkhir]);

        return [
            'message' => 'Pembeli berhasil diperbarui',
            'poin_awal' => $poinAwal,
            'poin_akhir' => $poinAkhir,
            'pembeli' => $pembeli
        ];
    }

}
