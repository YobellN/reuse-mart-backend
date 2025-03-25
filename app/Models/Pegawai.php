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

    public $timestamps = false;

    protected $casts = [
        'id_pegawai' => 'string'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function jabatan() {
        return $this->belongsTo(Jabatan::class, 'id_jabatan');
    }
}
