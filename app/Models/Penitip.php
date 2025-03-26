<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
