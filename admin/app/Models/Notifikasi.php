<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'pengaduan_id',
        'type',
        'judul',
        'pesan',
        'is_read',
        'pengaduan_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
