<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiMerchandise extends Model
{
    protected $table = 'transaksi_merchandise';
    protected $primaryKey = 'id_transaksi_merchandise';
    public $timestamps = false;

    protected $fillable = [
        'id_pembeli',
        'id_merchandise',
        'tanggal_transaksi',
        'status_transaksi',
        'tanggal_pengambilan',
    ];

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class, 'id_merchandise', 'id_merchandise');
    }

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'id_pembeli', 'id_pembeli');
    }
    
}
