<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\Models\Penitipan;
use App\Services\FcmChannel;
use Illuminate\Console\Command;

class NotifPenitipan extends Command
{
    protected $signature = 'app:notif-penitipan';
    protected $description = 'Kirim notifikasi H-3 dan Hari H, serta update status produk sesuai tenggat.';

    public function handle()
    {
        try {
            $today = Carbon::today();
            $reminderDate = $today->copy()->addDays(3)->toDateString();
            $expiredDate = $today->toDateString();

            // NOTIFIKASI H-3
            $penitipanReminder = Penitipan::with('detailPenitipan.produk')
                ->join('detail_penitipan', 'penitipan.id_penitipan', '=', 'detail_penitipan.id_penitipan')
                ->join('produk', 'detail_penitipan.id_produk', '=', 'produk.id_produk')
                ->select('penitipan.*')
                ->whereDate('tenggat_penitipan', $reminderDate)
                ->whereNull('produk.status_akhir_produk')
                ->distinct('penitipan.id_penitipan')
                ->get();

            // NOTIFIKASI Hari H
            $penitipanExpired = Penitipan::with('detailPenitipan.produk')
                ->join('detail_penitipan', 'penitipan.id_penitipan', '=', 'detail_penitipan.id_penitipan')
                ->join('produk', 'detail_penitipan.id_produk', '=', 'produk.id_produk')
                ->select('penitipan.*')
                ->whereDate('tenggat_penitipan', $expiredDate)
                ->whereNull('produk.status_akhir_produk')
                ->distinct('penitipan.id_penitipan')
                ->get();

            if ($penitipanReminder->isEmpty() && $penitipanExpired->isEmpty()) {
                $this->info('Tidak ada penitipan yang akan segera habis atau habis hari ini.');
            }

            // Kirim notifikasi H-3
            foreach ($penitipanReminder as $penitipan) {
                $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' (H-3)');
                $fcmPenitip = $penitipan->penitip()->first()?->user?->fcm_token;

                if ($fcmPenitip) {
                    FcmChannel::send(
                        $fcmPenitip,
                        'Pengingat H-3 Masa Titip Barang Habis!',
                        'Barang Anda akan habis pada tanggal ' .
                            Carbon::parse($penitipan->tenggat_penitipan)->locale('id')->isoFormat('dddd, D MMMM YYYY')
                    );
                }
            }

            // Kirim notifikasi Hari H + logika update ketersediaan
            foreach ($penitipanExpired as $penitipan) {
                $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' (Hari H)');
                $fcmPenitip = $penitipan->penitip()->first()?->user?->fcm_token;

                if ($fcmPenitip) {
                    FcmChannel::send(
                        $fcmPenitip,
                        'Pengingat Masa Titip Barang Habis!',
                        'Masa titip barang Anda telah habis pada tanggal ' .
                            Carbon::parse($penitipan->tenggat_penitipan)->locale('id')->isoFormat('dddd, D MMMM YYYY') .
                            '. Silakan segera mengambil barang Anda.'
                    );
                }

                // Update status_ketersediaan → 0
                foreach ($penitipan->detailPenitipan as $detail) {
                    $produk = $detail->produk;
                    if ($produk && $produk->status_ketersediaan != 0) {
                        $produk->status_ketersediaan = 0;
                        $produk->save();
                    }
                }

                $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' → produk diset tidak tersedia (status_ketersediaan = 0)');
            }

            // Update status_akhir_produk → 'Produk untuk donasi' jika tenggat_pengambilan hari ini
            $penitipanUntukDonasi = Penitipan::with('detailPenitipan.produk')
                ->whereDate('tenggat_pengambilan', $today)
                ->get();

            foreach ($penitipanUntukDonasi as $penitipan) {
                $adaYangDiubah = false;

                foreach ($penitipan->detailPenitipan as $detail) {
                    $produk = $detail->produk;
                    if ($produk && is_null($produk->status_akhir_produk)) {
                        $produk->status_akhir_produk = 'Produk untuk donasi';
                        $produk->save();
                        $adaYangDiubah = true;
                    }
                }

                if ($adaYangDiubah) {
                    $this->info('Penitipan ID ' . $penitipan->id_penitipan . ' → produk didonasikan (status_akhir_produk = "Produk untuk donasi")');
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
