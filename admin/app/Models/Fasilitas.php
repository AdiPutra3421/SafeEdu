<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Fasilitas extends Model
{
    protected $table = 'fasilitas';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (Schema::hasTable('fasilitas')) {
            $this->table = 'fasilitas';
        }
    }

    protected $fillable = [
        'nama',
        'nama_fasilitas',
        'lokasi',
        'status_kondisi',
        'kondisi',
        'deskripsi',
        'foto',
    ];

    public function pengaduans()
    {
        $fk = Schema::hasColumn('pengaduan', 'id_fasilitas') ? 'id_fasilitas' : 'fasilitas_id';

        return $this->hasMany(Pengaduan::class, $fk);
    }
}
