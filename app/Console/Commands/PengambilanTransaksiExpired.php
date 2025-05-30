<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\Models\Penjualan;
use Illuminate\Console\Command;
use App\Services\PenjualanService;

class PengambilanTransaksiExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pengambilan-transaksi-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $expiredDate = Carbon::now()->subDays(2);

            $penjualans = Penjualan::with('detail.produk')
                ->where('status_penjualan', 'Menunggu Pengambilan')
                ->where('jadwal_pengambilan', '<', $expiredDate)
                ->get();

            foreach ($penjualans as $penjualan) {
                $penjualan->update(['status_penjualan' => 'Hangus']);

                foreach ($penjualan->detail as $item) {
                    $produk = $item->produk;

                    $produk->update([
                        'status_akhir_produk' => 'Produk untuk donasi'
                    ]);
                    $komisi = PenjualanService::hitungKomisi($produk);
                }

                $poin = PenjualanService::tambahPoin($penjualan);

                $this->info('Penjualan dengan ID ' . $penjualan->id . ' telah dihanguskan. Poin: ' . json_encode($poin) . ', Komisi: ' . json_encode($komisi));

            }

            $this->info("Berhasil menghanguskan " . $penjualans->count() . " penjualan.");
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
