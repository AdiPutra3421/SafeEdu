<?php

namespace Tests\Feature;

use App\Events\NotifikasiDikirim;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'siswa',
            'password' => Hash::make('old-password'),
        ]);

        $this->postJson('/api/auth/profile', [
            'user_id' => $user->id,
            'nama' => 'Nama Baru',
            'password' => 'new-password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_storage_route_rejects_path_traversal_attempts(): void
    {
        $this->get('/storage/../../.env')->assertNotFound();
    }

    public function test_report_routes_have_unique_names(): void
    {
        $this->assertSame('/laporan', route('laporan', [], false));
        $this->assertSame('/laporan/42', route('laporan.detail', ['id' => 42], false));
    }

    public function test_notification_event_uses_a_private_recipient_channel(): void
    {
        $notification = new Notifikasi([
            'user_id' => 42,
            'judul' => 'Laporan Diproses',
            'pesan' => 'Laporan sedang diproses.',
        ]);
        $channel = (new NotifikasiDikirim($notification))->broadcastOn()[0];

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-notifikasi.42', $channel->name);
    }
}
