<?php

namespace App\Console\Commands;
use Exception;
use Carbon\Carbon;
use App\Models\Penitipan;
use App\Services\FcmChannel;

use Illuminate\Console\Command;

class NotifPenitipan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notif-penitipan';

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
        $today = Carbon::today();
        $reminderDate = Carbon::now()->addDays(3)->toDateString(); 
        $expiredDate = Carbon::now()->toDateString();              

       $penitipanReminder = Penitipan::with('detailPenitipan.produk')
            ->join('detail_penitipan', 'penitipan.id_penitipan', '=', 'detail_penitipan.id_penitipan')
            ->join('produk', 'detail_penitipan.id_produk', '=', 'produk.id_produk')
            ->select('penitipan.*')
            ->whereDate('tenggat_penitipan', $reminderDate)
            ->whereNull('status_akhir_produk')
            ->distinct('penitipan.id_penitipan')
            ->get();


       $penitipanExpired = Penitipan::with('detailPenitipan.produk')
            ->join('detail_penitipan', 'penitipan.id_penitipan', '=', 'detail_penitipan.id_penitipan')
            ->join('produk', 'detail_penitipan.id_produk', '=', 'produk.id_produk')
            ->select('penitipan.*')
            ->whereDate('tenggat_penitipan', $expiredDate)
            ->whereNull('status_akhir_produk')
            ->distinct('penitipan.id_penitipan')
            ->get();


        if ($penitipanReminder->isEmpty() && $penitipanExpired->isEmpty()) {
            $this->info('Tidak ada penitipan yang akan segera habis atau habis hari ini.');
        }

        foreach ($penitipanReminder as $penitipan) {
            $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' (H-3)');
            $fcmPenitip = $penitipan->penitip()->first()->user->fcm_token;

            if ($fcmPenitip) {
                $notifPenitip = FcmChannel::send(
                    $fcmPenitip,
                    'Pengingat H-3 Masa Titip Barang Habis!',
                    'Barang Anda akan habis pada tanggal ' . Carbon::parse($penitipan->tenggat_penitipan)->locale('id')->isoFormat('dddd, D MMMM YYYY')
                );
            }
        }

        //penitipan habis hari ini tenggatnya
        foreach ($penitipanExpired as $penitipan) {
            $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' (Hari H)');
            $fcmPenitip = $penitipan->penitip()->first()->user->fcm_token;

            if ($fcmPenitip) {
                $notifPenitip = FcmChannel::send(
                    $fcmPenitip,
                    'Pengingat Masa Titip Barang Habis!',
                    'Masa titip barang Anda telah habis pada tanggal ' . Carbon::parse($penitipan->tenggat_penitipan)->locale('id')->isoFormat('dddd, D MMMM YYYY') . '. Silakan segera mengambil barang Anda.'
                );
            }

            // ubah status produk ke tidak laku klo penitipannya habis hari ini
            if (is_null($penitipan->status_akhir_produk)) {
                foreach ($penitipan->detailPenitipan as $detail) {
                    if ($detail->produk) {
                        $detail->produk->status_akhir_produk = 'Tidak Laku';
                        $detail->produk->save();
                    }
                }
                $penitipan->status_akhir_produk = 'Tidak Laku';
                $penitipan->save();
                $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' status diubah menjadi "Tidak Laku"');
            }

            //ubah jadi barang ke list donasi kalo gak diambil 7 hari
           if (
                $penitipan->batas_pengambilan &&
                $penitipan->batas_pengambilan == Carbon::today() &&
                $penitipan->status_akhir_produk === 'Tidak Laku'
            ) {
                foreach ($penitipan->detailPenitipan as $detail) {
                    if ($detail->produk) {
                        $detail->produk->status_akhir_produk = 'Produk untuk donasi';
                        $detail->produk->save();
                    }
                }
                $penitipan->status_akhir_produk = 'Produk untuk donasi';
                $penitipan->save();
                $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' status diubah menjadi "Produk untuk donasi"');
            }
        }

    } catch (Exception $e) {
        $this->error($e->getMessage());
    }
    }

}
