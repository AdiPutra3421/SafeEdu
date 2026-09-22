<?php

namespace App\Events;

use App\Models\Notifikasi;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifikasiDikirim implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notifikasi $notifikasi;

    public function __construct(Notifikasi $notifikasi)
    {
        $this->notifikasi = $notifikasi;
    }

    /**
     * Channel user pribadi: private-notifikasi.{user_id}
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('notifikasi.' . $this->notifikasi->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notifikasi.baru';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->notifikasi->id,
            'judul'        => $this->notifikasi->judul,
            'pesan'        => $this->notifikasi->pesan,
            'type'         => $this->notifikasi->type,
            'pengaduan_id' => $this->notifikasi->pengaduan_id,
            'is_read'      => false,
            'created_at'   => $this->notifikasi->created_at?->toIso8601String(),
        ];
    }
}
