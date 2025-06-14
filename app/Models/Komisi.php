<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komisi extends Model
{
    protected $primaryKey = 'id_detail_penjualan';
    protected $table = 'komisi';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_detail_penjualan',
        'id_penitip',
        'id_hunter',
        'harga_jual',
        'komisi_perusahaan',
        'komisi_penitip',
        'komisi_hunter',
        'bonus_penitip',
        'ditambahkan',
    ];

    protected $casts = [
        'komisi_perusahaan' => 'float',
        'komisi_penitip' => 'float',
        'komisi_hunter' => 'float',
        'bonus_penitip' => 'float',
        'ditambahkan' => 'integer',
    ];

    public function detail()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan', 'id_detail_penjualan');
    }

    public function hunter()
    {
        return $this->belongsTo(Pegawai::class, 'id_hunter', 'id_pegawai');
    }

    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'id_penitip', 'id_penitip');
    }
}
