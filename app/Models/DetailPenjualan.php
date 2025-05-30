<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';
    
    protected $primaryKey = 'id_detail_penjualan';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'id_penjualan',
        'id_produk',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    public function komisi() 
    {
        return $this->hasOne(Komisi::class, 'id_detail_penjualan', 'id_detail_penjualan');
    }
}
