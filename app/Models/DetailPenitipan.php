<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenitipan extends Model
{
    protected $table = 'detail_penitipan';
    public $timestamps = false;

    protected $fillable = [
        'id_penitipan',
        'id_produk',
    ];

    public function penitipan()
    {
        return $this->belongsTo(Penitipan::class, 'id_penitipan', 'id_penitipan');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
