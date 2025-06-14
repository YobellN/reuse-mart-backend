<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Omaressaouaf\LaravelIdGenerator\IdGenerator;

class Penitip extends Model
{
    protected $table = 'penitip';

    protected $primaryKey = 'id_penitip';

    protected $fillable = [
        'id_penitip',
        'id_user',
        'nik',
        'foto_ktp',
        'saldo',
        'poin',
    ];

    public $timestamps = false;

    public $incrementing = false;

    public $casts = [
        'saldo' => 'float'
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id_penitip = IdGenerator::generate(Penitip::class, 'id_penitip', 4, 'T');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function topSeller()
    {
        return $this->hasMany(TopSeller::class, 'id_penitip', 'id_penitip');
    }

}
