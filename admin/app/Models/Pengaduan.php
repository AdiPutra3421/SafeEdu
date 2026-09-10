<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    protected $table = 'pengaduan';

    protected $fillable = [
        'user_id',
        'kategori_id',
        'fasilitas_id',
        'judul',
        'deskripsi',
        'lokasi_kejadian',
        'status',
        'is_anonim',
        'foto_bukti',
        'rating_kepuasan',
        'ulasan_pelapor',
    ];

    protected $casts = [
        'is_anonim' => 'boolean',
    ];

    public function pelapor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function fasilitas()
    {
        return $this->belongsTo(Fasilitas::class, 'fasilitas_id');
    }

    public function penugasans()
    {
        return $this->hasMany(Penugasan::class, 'pengaduan_id');
    }
}
