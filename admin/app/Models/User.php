<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nama',
        'name',
        'email',
        'password',
        'role',
        'bidang',
    ];

    public function getNameAttribute()
    {
        return $this->nama;
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getDisplayEmailAttribute()
    {
        $rawEmail = (string) ($this->attributes['email'] ?? '');
        if (str_contains($rawEmail, '@')) {
            return $rawEmail;
        }

        return $rawEmail ? ($rawEmail.'@safeedu.sch.id') : '-';
    }

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
