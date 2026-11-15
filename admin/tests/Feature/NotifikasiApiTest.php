<?php

namespace Tests\Feature;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_fetch_and_mark_their_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notifikasi::create([
            'user_id' => $user->id,
            'judul' => 'Laporan Diproses',
            'pesan' => 'Laporan Anda sedang ditangani.',
            'type' => 'info',
        ]);

        $this->getJson("/api/notifikasi?user_id={$user->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.is_read', false);

        $this->postJson("/api/notifikasi/{$notification->id}/read")
            ->assertOk();

        $this->assertDatabaseHas('notifikasi', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }
}
