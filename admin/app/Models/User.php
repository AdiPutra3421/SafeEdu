<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'nama',
        'email',
        'email_verified_at',
        'password',
        'role',
        'remember_token',
    ];

    public function getNameAttribute()
    {
        return $this->attributes['name'] ?? $this->attributes['nama'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['nama'] = $value;
    }

    public function getNamaAttribute()
    {
        return $this->attributes['nama'] ?? $this->attributes['name'] ?? null;
    }

    public function setNamaAttribute($value): void
    {
        $this->attributes['nama'] = $value;
        $this->attributes['name'] = $value;
    }

    public function getDisplayEmailAttribute()
    {
        $rawEmail = (string)($this->attributes['email'] ?? '');
        if (str_contains($rawEmail, '@')) {
            return $rawEmail;
        }
        return $rawEmail ? ($rawEmail . '@safeedu.sch.id') : '-';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function pengaduans()
    {
        $fk = Schema::hasColumn('pengaduan', 'id_user') ? 'id_user' : 'user_id';

        return $this->hasMany(Pengaduan::class, $fk);
    }

    public function penugasansSebagaiTeknisi()
    {
        $fk = Schema::hasColumn('penugasan', 'id_teknisi') ? 'id_teknisi' : 'teknisi_id';

        return $this->hasMany(Penugasan::class, $fk);
    }

    public function penugasansSebagaiAdmin()
    {
        $fk = Schema::hasColumn('penugasan', 'id_admin') ? 'id_admin' : 'admin_id';

        return $this->hasMany(Penugasan::class, $fk);
    }

    public function notifikasis()
    {
        $fk = Schema::hasColumn('notifikasi', 'id_user') ? 'id_user' : 'user_id';

        return $this->hasMany(Notifikasi::class, $fk);
    }
}
