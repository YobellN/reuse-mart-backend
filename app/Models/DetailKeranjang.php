<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKeranjang extends Model
{
    protected $table = 'detail_keranjang';
    public $timestamps = false;
    // protected $primaryKey = null;

    // protected $keyType = 'string';

    protected $casts = [
        'id_produk' => 'string',
    ];

    protected $fillable = [
        'id_keranjang',
        'id_produk',
    ];

    public function keranjang()
    {
        return $this->belongsTo(Keranjang::class, 'id_keranjang', 'id_keranjang');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
