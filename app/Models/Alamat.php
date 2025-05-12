<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alamat extends Model
{
    protected $table = 'alamat';
    
    protected $primaryKey = 'id_alamat';
    public $timestamps = false;

    protected $fillable = [
        'id_alamat',
        'id_pembeli',
        'label',
        'kabupaten_kota',
        'kecamatan',
        'kode_pos',
        'alamat_utama',
        'detail_alamat'
    ];

   public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'id_alamat', 'id_alamat');
    }

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'id_pembeli', 'id_pembeli');
    }
}
