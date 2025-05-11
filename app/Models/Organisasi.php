<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Omaressaouaf\LaravelIdGenerator\IdGenerator;

class Organisasi extends Model
{
    protected $table = 'organisasi';
    protected $primaryKey = 'id_organisasi';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_organisasi',
        'id_user',
        'no_sk',
        'jenis_organisasi',
        'alamat_organisasi',
    ];

    public $timestamps = false;

    protected $casts = [
        'id_organisasi' => 'string',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id_organisasi = IdGenerator::generate(Organisasi::class, 'id_organisasi', 4, 'ORG');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function requestDonasi()
    {
        return $this->hasMany(RequestDonasi::class, 'id_organisasi');
    }

}
