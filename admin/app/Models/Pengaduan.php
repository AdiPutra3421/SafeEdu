<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Pengaduan extends Model
{
    protected $table = 'pengaduan';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (Schema::hasTable('pengaduan')) {
            $this->table = 'pengaduan';
        }
    }

    protected $fillable = [
        'user_id',
        'id_user',
        'kategori_id',
        'id_kategori',
        'fasilitas_id',
        'id_fasilitas',
        'judul',
        'deskripsi',
        'isi_pengaduan',
        'lokasi_kejadian',
        'lokasi',
        'urgency',
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
        $fk = Schema::hasColumn($this->getTable(), 'id_user') ? 'id_user' : 'user_id';

        return $this->belongsTo(User::class, $fk);
    }

    public function kategori()
    {
        $fk = Schema::hasColumn($this->getTable(), 'id_kategori') ? 'id_kategori' : 'kategori_id';

        return $this->belongsTo(Kategori::class, $fk);
    }

    public function fasilitas()
    {
        $fk = Schema::hasColumn($this->getTable(), 'id_fasilitas') ? 'id_fasilitas' : 'fasilitas_id';

        return $this->belongsTo(Fasilitas::class, $fk);
    }

    public function penugasans()
    {
        $fk = Schema::hasColumn('penugasan', 'id_pengaduan') ? 'id_pengaduan' : 'pengaduan_id';

        return $this->hasMany(Penugasan::class, $fk);
    }
}
