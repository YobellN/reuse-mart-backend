<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donasi extends Model
{
    protected $table = 'donasi';
    protected $primaryKey = 'id_donasi';
    public $timestamps = false;

    protected $fillable = [
        'id_donasi',
        'id_request_donasi',
        'id_produk',
        'tanggal_donasi',
        'nama_penerima',
        'total_poin',
    ];

    public function requestDonasi()
    {
        return $this->belongsTo(RequestDonasi::class, 'id_request_donasi');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }


}
