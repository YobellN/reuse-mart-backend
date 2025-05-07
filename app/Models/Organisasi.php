<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organisasi extends Model
{
    protected $table = 'organisasi';
    protected $primaryKey = 'id_organisasi';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'id_organisasi' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function requestDonasi()
    {
        return $this->hasMany(RequestDonasi::class, 'id_organisasi');
    }

}
