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
        'file_bukti_url',
        'rating_kepuasan',
        'ulasan_pelapor',
        'verified_at',
        'assigned_at',
        'worked_at',
        'completed_at',
    ];

    protected $casts = [
        'is_anonim' => 'boolean',
        'verified_at' => 'datetime',
        'assigned_at' => 'datetime',
        'worked_at' => 'datetime',
        'completed_at' => 'datetime',
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
