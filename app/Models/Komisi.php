<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komisi extends Model
{
    protected $table = 'komisi';

    protected $fillable = [
        'id_detail_penjualan',
        'id_pegawai',
        'id_penitip',
        'komisi',
        'status_komisi',
    ];

    public function detail()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan', 'id_detail_penjualan');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_hunter', 'id_pegawai');
    }

    public function penitip()
    {
        return $this->belongsTo(Pegawai::class, 'id_penitip', 'id_pegawai');
    }
}
