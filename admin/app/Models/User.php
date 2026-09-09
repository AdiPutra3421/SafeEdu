<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'foto',
    ];

    protected $hidden = [
        'password',
    ];

    // Override the function to tell Laravel to look for 'password' column
    // (Walaupun namanya sama, untuk memastikan Auth Breeze/Sanctum bekerja 100%)
    public function getAuthPassword()
    {
        return $this->password;
    }
}
