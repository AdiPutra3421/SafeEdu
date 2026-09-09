<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    use HasFactory;

    protected $table = 'pengaduan';
    protected $primaryKey = 'id_pengaduan';

    protected $fillable = [
        'id_user',
        'id_kategori',
        'id_fasilitas',
        'judul',
        'isi_pengaduan',
        'lokasi',
        'status',
        'alasan_penolakan',
        'tanggal_pengaduan',
        'tanggal_selesai',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriPengaduan::class, 'id_kategori', 'id_kategori');
    }

    public function fasilitas()
    {
        return $this->belongsTo(Fasilitas::class, 'id_fasilitas', 'id_fasilitas');
    }

    public function penugasan()
    {
        return $this->hasMany(Penugasan::class, 'id_pengaduan', 'id_pengaduan');
    }

    public function buktiPengaduan()
    {
        return $this->hasMany(BuktiPengaduan::class, 'id_pengaduan', 'id_pengaduan');
    }
}
