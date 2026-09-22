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
        'isi',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}