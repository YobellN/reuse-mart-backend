<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diskusi extends Model
{
    protected $table = 'diskusi';

    protected $primaryKey = 'id_diskusi';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'id_diskusi',
        'id_user',
        'id_produk',
        'pesan',
        'timestamp'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
