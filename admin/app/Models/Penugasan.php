<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penugasan extends Model
{
    protected $table = 'penugasan';

    protected $fillable = [
        'pengaduan_id',
        'teknisi_id',
        'admin_id',
        'catatan_admin',
        'status',
    ];

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id');
    }

    public function teknisi()
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function logPengerjaans()
    {
        return $this->hasMany(LogPengerjaan::class, 'penugasan_id');
    }
}
