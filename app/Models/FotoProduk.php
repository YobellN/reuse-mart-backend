<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoProduk extends Model
{
    protected $table = 'foto_produk';
    protected $primaryKey = 'id_foto_produk';
    public $timestamps = false;
    protected $fillable = [
        'id_produk',
        'path_foto',
        'thumbnail',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }
}
