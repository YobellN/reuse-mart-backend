<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawai';

    protected $primaryKey = 'id_pegawai';

    protected $fillable = [
        'id_pegawai',
        'id_user',
        'id_jabatan',
        'nip',
        'tanggal_lahir',
        'komisi',
    ];

}
