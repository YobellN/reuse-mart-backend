<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
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
        'role',
    ];

    public $timestamps = false;
}
