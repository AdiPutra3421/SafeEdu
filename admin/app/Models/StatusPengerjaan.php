<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusPengerjaan extends Model
{
    use HasFactory;

    protected $table = 'status_pengerjaan';
    protected $primaryKey = 'id_status';

    protected $fillable = [
        'id_penugasan',
        'status',
        'keterangan',
        'tanggal_update',
    ];

    public function penugasan()
    {
        return $this->belongsTo(Penugasan::class, 'id_penugasan', 'id_penugasan');
    }
}
