<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    protected $table = 'fasilitas';

    protected $fillable = [
        'nama',
        'lokasi',
        'status_kondisi',
        'deskripsi',
        'foto_fasilitas',
    ];

    public function pengaduans()
    {
        return $this->hasMany(Pengaduan::class, 'fasilitas_id');
    }
}
