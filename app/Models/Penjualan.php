<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $table = 'penjualan';

    protected $primaryKey = 'id_penjualan';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_penjualan',
        'id_pembeli',
        'tanggal_penjualan',
        'metode_pengiriman',
        'jadwal_pengambilan',
        'total_ongkir',
        'poin_potongan',
        'total_harga',
        'poin_perolehan',
        'total_poin',
        'status_penjualan',
        'tenggat_pembayaran',
        'status',
    ];

    public function pengiriman() 
    {
        return $this->hasOne(Pengiriman::class, 'id_penjualan', 'id_penjualan');
    }

    public function pembayaran() 
    {
        return $this->hasOne(Pembayaran::class, 'id_penjualan', 'id_penjualan');
    }

    public function detail() 
    {
        return $this->hasMany(DetailPenjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function pembeli() 
    {
        return $this->belongsTo(Pembeli::class, 'id_pembeli', 'id_pembeli');
    }
}
