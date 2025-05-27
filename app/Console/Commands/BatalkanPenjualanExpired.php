<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DetailPenjualan;
use App\Models\Produk;

class BatalkanPenjualanExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:batalkan-penjualan-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     */
    // JANGAN LUPA UNTUK BIKIN KETIKA ADMIN TOLAK PEMBAYARAN
    public function handle()
    {
        $penjualans = \App\Models\Penjualan::where('status_penjualan', 'Menunggu Pembayaran')
            ->where('tenggat_pembayaran', '<', now())
            ->doesntHave('pembayaran') // tidak ada relasi pembayaran
            ->get();

        foreach ($penjualans as $penjualan) {
            $penjualan->status_penjualan = 'Hangus';
            $penjualan->save();

            // membalikkan stok produk
            $detail_penjualan = DetailPenjualan::where('id_penjualan', $penjualan->id_penjualan)->get();
            foreach ($detail_penjualan as $detail) {
                $produk = Produk::find($detail->id_produk);
                $produk->status_ketersediaan = 1;
                $produk->save();
            }

            // Hapus pengiriman jika ada
            if ($penjualan->pengiriman) {
                $penjualan->pengiriman->delete();
            }

            $pembeli = $penjualan->pembeli;
            if ($pembeli) {
                $pembeli->poin += $penjualan->poin_potongan - $penjualan->poin_perolehan;
                $pembeli->save();
            }
        }

        $this->info(count($penjualans) . ' penjualan dibatalkan karena lewat tenggat.');
    }
}
