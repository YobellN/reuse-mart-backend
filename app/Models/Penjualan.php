<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Penjualan extends Model
{
    protected $table = 'penjualan';

    protected $primaryKey = 'id_penjualan';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_penjualan',
        'id_pembeli',
        'tanggal_penjualan',
        'metode_pengiriman',
        'jadwal_pengambilan',
        'total_ongkir',
        'poin_potongan',
        'total_harga',
        'poin_perolehan',
        'total_poin',
        'status_penjualan',
        'tenggat_pembayaran',
        'status',
    ];

    protected $casts = [
        'id_penjualan' => 'string',
        'id_pembeli' => 'string',
        'tanggal_penjualan' => 'datetime',
        'jadwal_pengambilan' => 'datetime',
        'total_ongkir' => 'float',
        'poin_potongan' => 'integer',
        'total_harga' => 'float',
        'poin_perolehan' => 'integer',
        'total_poin' => 'integer',
        'tenggat_pembayaran' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $tahun = now()->format('y');
            $bulan = now()->format('m');
            $prefix = "{$tahun}.{$bulan}.";

            $lastId = DB::table('penjualan')
                ->where('id_penjualan', 'like', "{$prefix}%")
                ->orderByDesc('id_penjualan')
                ->value('id_penjualan');

            if ($lastId) {
                $lastNumber = (int) substr($lastId, -4);
            } else {
                $lastNumber = 0;
            }

            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            $model->id_penjualan = "{$tahun}.{$bulan}.{$nextNumber}";
        });
    }

    public function pengiriman() 
    {
        return $this->hasOne(Pengiriman::class, 'id_penjualan', 'id_penjualan');
    }

    public function pembayaran() 
    {
        return $this->hasOne(Pembayaran::class, 'id_penjualan', 'id_penjualan');
    }

    public function detail()
    {
        return $this->hasMany(DetailPenjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function pembeli() 
    {
        return $this->belongsTo(Pembeli::class, 'id_pembeli', 'id_pembeli');
    }
}
