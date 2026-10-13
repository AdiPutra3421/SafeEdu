<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontenSekolah extends Model
{
    protected $table = 'konten_sekolah';

    protected $fillable = [
        'tipe',
        'judul',
        'ringkasan',
        'gambar',
        'isi',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    protected $appends = [
        'gambar_url',
    ];

    public function getGambarUrlAttribute(): ?string
    {
        if (! $this->gambar) {
            return null;
        }

        if (str_starts_with($this->gambar, 'http://') || str_starts_with($this->gambar, 'https://')) {
            return $this->gambar;
        }

        return url('storage/' . $this->gambar);
    }
}