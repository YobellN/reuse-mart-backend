<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keranjang extends Model
{
    protected $table = 'keranjang';
    protected $primaryKey = 'id_keranjang';
    public $timestamps = false;
    protected $fillable = [
        'id_keranjang',
        'id_pembeli'
    ];

    public function pembeli()
    {
        return $this->belongsTo(User::class, 'id_pembeli', 'id_pembeli');
    }

    public function detailKeranjang()
    {
        return $this->hasMany(DetailKeranjang::class, 'id_keranjang', 'id_keranjang');
    }
}
