<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopSeller extends Model
{
    protected $table = 'top_seller';
    protected $primaryKey = 'id_top_seller';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

     protected $fillable = [
        'id_penitip',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_penjualan',
        'bonus',
    ];

    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'id_penitip', 'id_penitip');
    }
}
