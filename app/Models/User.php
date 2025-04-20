<?php

namespace App\Models;

use Illuminate\Contracts\Auth\CanResetPassword;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user';
    protected $primaryKey = 'id_user'; 

    protected $fillable = [
        'nama', 
        'email', 
        'password', 
        'no_telp', 
        'role', 
        'fcm_token'
    ];
    

    protected $hidden = [
        'id_user',
        'password', 
    ];

    public $timestamps = false;
    

    public function penitip() {
        return $this->hasOne(Penitip::class, 'id_user');
    }

    public function pegawai() {
        return $this->hasOne(Pegawai::class, 'id_user');
    }

    public function pembeli() {
        return $this->hasOne(Pembeli::class, 'id_user');
    }

    public function organisasi() {
        return $this->hasOne(Organisasi::class, 'id_user');
    }
}
