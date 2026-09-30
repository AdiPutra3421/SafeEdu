<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'nama',
        'email',
        'nisn',
        'kelas',
        'password',
        'role',
    ];

    public function getNameAttribute()
    {
        return $this->nama;
    }

    public function getNisnAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }
        if (preg_match('/^[0-9]{10}$/', (string)$this->attributes['email'] ?? '')) {
            return $this->attributes['email'];
        }
        return null;
    }

    public function getKelasAttribute($value)
    {
        return $value ?: ($this->role === 'siswa' ? 'XII Rekayasa Perangkat Lunak 1' : null);
    }

    public function getDisplayEmailAttribute()
    {
        $rawEmail = (string)($this->attributes['email'] ?? '');
        if (str_contains($rawEmail, '@')) {
            return $rawEmail;
        }
        $identifier = $this->nisn ?: $rawEmail;
        return $identifier ? ($identifier . '@safeedu.sch.id') : '-';
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
