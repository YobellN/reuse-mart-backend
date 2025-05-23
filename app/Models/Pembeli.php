<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Omaressaouaf\LaravelIdGenerator\IdGenerator;


class Pembeli extends Model
{
    protected $table = 'pembeli';
    
    protected $primaryKey = 'id_pembeli';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_pembeli',
        'id_user',
        'poin',
        'otp',
        'otp_created_at',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id_pembeli = IdGenerator::generate(Pembeli::class, 'id_pembeli', 4, 'PB');
        });
    }


    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_pembeli', 'id_pembeli');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function alamat()
    {
        return $this->hasMany(Alamat::class, 'id_pembeli', 'id_pembeli');
    }
    
    public function keranjang()
    {
        return $this->hasOne(Keranjang::class, 'id_pembeli', 'id_pembeli');
    }
}
