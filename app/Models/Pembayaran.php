<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_penjualan';
    protected $keyType = 'string';
    protected $fillable = [
        'id_penjualan',
        'tanggal_pembayaran',
        'metode_pembayaran',
        'status_pembayaran',
        'bukti_pembayaran',
    ];


    public $timestamps = false;
    public $incrementing = false;
    
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }
}
