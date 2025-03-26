<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organisasi extends Model
{
    protected $table = 'organisasi';

    protected $primaryKey = 'id_organisasi';

    protected $casts = [
        'id_organisasi' => 'string',
    ];
}
