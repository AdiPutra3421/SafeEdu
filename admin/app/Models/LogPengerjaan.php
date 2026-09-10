<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPengerjaan extends Model
{
    protected $table = 'log_pengerjaan';

    protected $fillable = [
        'penugasan_id',
        'catatan_teknisi',
        'foto_hasil',
    ];

    public function penugasan()
    {
        return $this->belongsTo(Penugasan::class, 'penugasan_id');
    }
}
