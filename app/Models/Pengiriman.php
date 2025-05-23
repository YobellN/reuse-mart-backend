<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
    protected $table = 'pengiriman';

    public $timestamps = false;

    protected $primaryKey = 'id_penjualan';

    protected $keyType = 'string';

    protected $fillable = [
        'id_penjualan',
        'id_kurir',
        'id_alamat',
        'jadwal_pengiriman',
        'status_pengiriman',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function kurir()
    {
        return $this->belongsTo(Pegawai::class, 'id_kurir', 'id_pegawai');
    }

    public function alamat()
    {
        return $this->belongsTo(Alamat::class, 'id_alamat', 'id_alamat');
    }
}
