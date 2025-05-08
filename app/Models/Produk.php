<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';

    protected $primaryKey = 'id_produk';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_produk',
        'id_kategori',
        'nama_produk',
        'deskripsi_produk',
        'harga_produk',
        'foto_produk',
        'status_akhir_produk',
        'status_ketersediaan',
        'status_garansi',
        'status_produk_hunting',
        'rating'
    ];

    protected $casts = [
        'id_produk' => 'string',
        'id_kategori' => 'string',
        'harga_produk' => 'float',
        'deskripsi_produk' => 'string',
        'status_akhir_produk' => 'string',
        'status_ketersediaan' => 'boolean',
        'status_garansi' => 'boolean',
        'status_produk_hunting' => 'boolean',
        'rating' => 'integer',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriProduk::class, 'id_kategori', 'id_kategori');
    }

    public function detailPenjualan()
    {
        return $this->hasMany(DetailPenjualan::class, 'id_produk', 'id_produk');
    }

    public function detailPenitipan()
    {
        return $this->hasOne(DetailPenitipan::class, 'id_produk', 'id_produk');
    }

    public function fotoProduk()
    {
        return $this->hasMany(FotoProduk::class, 'id_produk', 'id_produk');
    }
    
}
