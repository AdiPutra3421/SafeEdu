<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    public function pengaduans()
    {
        return $this->hasMany(Pengaduan::class, 'user_id');
    }

    public function penugasansSebagaiTeknisi()
    {
        return $this->hasMany(Penugasan::class, 'teknisi_id');
    }

    public function penugasansSebagaiAdmin()
    {
        return $this->hasMany(Penugasan::class, 'admin_id');
    }

    public function notifikasis()
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }
}
