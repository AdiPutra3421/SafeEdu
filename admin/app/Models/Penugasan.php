<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Penugasan extends Model
{
    protected $table = 'penugasan';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (Schema::hasTable('penugasan')) {
            $this->table = 'penugasan';
        }
    }

    protected $fillable = [
        'pengaduan_id',
        'id_pengaduan',
        'teknisi_id',
        'id_teknisi',
        'admin_id',
        'id_admin',
        'catatan_admin',
        'catatan',
        'tanggal_penugasan',
        'status',
        'status_penugasan',
        'rejection_reason',
    ];

    public function pengaduan()
    {
        $fk = Schema::hasColumn($this->getTable(), 'id_pengaduan') ? 'id_pengaduan' : 'pengaduan_id';

        return $this->belongsTo(Pengaduan::class, $fk);
    }

    public function teknisi()
    {
        $fk = Schema::hasColumn($this->getTable(), 'id_teknisi') ? 'id_teknisi' : 'teknisi_id';

        return $this->belongsTo(User::class, $fk);
    }

    public function admin()
    {
        $fk = Schema::hasColumn($this->getTable(), 'id_admin') ? 'id_admin' : 'admin_id';

        return $this->belongsTo(User::class, $fk);
    }

    public function logPengerjaans()
    {
        $fk = Schema::hasColumn('log_pengerjaan', 'id_penugasan') ? 'id_penugasan' : 'penugasan_id';

        return $this->hasMany(LogPengerjaan::class, $fk);
    }
}
