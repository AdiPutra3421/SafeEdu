<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Kategori extends Model
{
    protected $table = 'kategori';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (Schema::hasTable('kategori_pengaduan')) {
            $this->table = 'kategori_pengaduan';
        }
    }

    protected $fillable = [
        'jenis',
        'nama',
        'nama_kategori',
        'deskripsi',
    ];

    public function pengaduans()
    {
        $fk = Schema::hasColumn('pengaduan', 'id_kategori') ? 'id_kategori' : 'kategori_id';

        return $this->hasMany(Pengaduan::class, $fk);
    }
}
