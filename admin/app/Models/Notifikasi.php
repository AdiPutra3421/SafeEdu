<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (Schema::hasTable('notifikasi')) {
            $this->table = 'notifikasi';
        }
    }

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'is_read',
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
