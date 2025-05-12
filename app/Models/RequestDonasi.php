<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestDonasi extends Model
{
    protected $table = 'request_donasi';
    protected $primaryKey = 'id_request_donasi';
    public $timestamps = false;

    protected $fillable = [
        'id_request_donasi',
        'id_organisasi',
        'tanggal_request',
        'deskripsi_request',
        'status_request',
    ];

    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi');
    }

    public function donasi()
    {
        return $this->hasOne(Donasi::class, 'id_request_donasi', 'id_request_donasi');
    }
}
