<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    protected $primaryKey = 'id_merchandise';
    protected $table = 'merchandise';
    public $timestamps = false;

    protected $fillable = [
        'id_merchandise',
        'nama_merchandise',
        'stok',
        'poin_penukaran',
        'foto_merchandise'
    ];

    protected $casts = [
        'stok' => 'integer',
        'poin_penukaran' => 'integer'
    ];

    public function transaksiMerchandise()
    {
        return $this->hasMany(TransaksiMerchandise::class, 'id_merchandise', 'id_merchandise');
    }
}
