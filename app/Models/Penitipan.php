<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penitipan extends Model
{
    protected $table = 'penitipan';
    protected $primaryKey = 'id_penitipan';
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_penitipan',
        'id_penitip',
        'id_qc',
        'id_hunter',
        'tanggal_penitipan',
        'tenggat_penitipan',
        'tenggat_pengambilan',
        'status_perpanjangan'
    ];
    
    public function penitip() {
        return $this->belongsTo(Penitip::class, 'id_penitip');
    }

    public function qc() {
        return $this->belongsTo(Pegawai::class, 'id_qc', 'id_pegawai');
    }

    public function hunter() {
        return $this->belongsTo(Pegawai::class, 'id_hunter', 'id_pegawai');
    }

    public function detailPenitipan() {
        return $this->hasMany(DetailPenitipan::class, 'id_penitipan', 'id_penitipan');
    }
}
