<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenugasanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_list_is_sorted_and_filtered_by_report_urgency(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Fasilitas']);

        foreach (['low', 'medium', 'urgent'] as $urgency) {
            $this->createTask($reporter, $teknisi, $admin, $kategori, $urgency);
        }

        $this->getJson('/api/penugasan')
            ->assertOk()
            ->assertJsonPath('data.0.urgency', 'urgent')
            ->assertJsonPath('data.0.pengaduan.urgency', 'urgent')
            ->assertJsonPath('data.1.pengaduan.urgency', 'medium')
            ->assertJsonPath('data.2.pengaduan.urgency', 'low');

        $this->getJson('/api/penugasan?urgency=low')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pengaduan.urgency', 'low');
    }

    public function test_technician_cannot_receive_more_than_five_active_tasks(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Fasilitas']);

        for ($i = 0; $i < 5; $i++) {
            $this->createTask($reporter, $teknisi, $admin, $kategori, 'medium');
        }

        $report = $this->createReport($reporter, $kategori, 'urgent');

        $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $teknisi->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Teknisi ini sudah memiliki 5 tugas aktif. Pilih teknisi lain atau tunggu tugas selesai.');
    }

    public function test_task_can_be_rejected_with_a_required_reason(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Fasilitas']);
        $task = $this->createTask($reporter, $teknisi, $admin, $kategori, 'medium');

        $this->postJson("/api/penugasan/{$task->id}/reject", [
            'rejection_reason' => 'Di luar keahlian teknisi.',
        ])->assertOk();

        $this->assertDatabaseHas('penugasan', [
            'id' => $task->id,
            'status' => 'ditolak',
            'rejection_reason' => 'Di luar keahlian teknisi.',
        ]);
        $this->assertDatabaseHas('pengaduan', [
            'id' => $task->pengaduan_id,
            'status' => 'ditolak',
        ]);
    }

    public function test_completing_a_task_requires_an_image_proof(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Fasilitas']);
        $task = $this->createTask($reporter, $teknisi, $admin, $kategori, 'medium');

        $this->postJson("/api/penugasan/{$task->id}/status", [
            'status' => 'selesai',
            'catatan_teknisi' => 'Perbaikan selesai.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_notification_creation_uses_only_supported_schema_columns(): void
    {
        $reporter = User::factory()->create(['role' => 'siswa']);

        $notification = Notifikasi::create([
            'user_id' => $reporter->id,
            'judul' => 'Laporan Diproses',
            'pesan' => 'Laporan Anda sedang diproses.',
            'is_read' => false,
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'id' => $notification->id,
            'user_id' => $reporter->id,
            'judul' => 'Laporan Diproses',
        ]);
        $this->assertFalse($notification->is_read);
    }

    private function createTask(
        User $reporter,
        User $teknisi,
        User $admin,
        Kategori $kategori,
        string $urgency,
    ): Penugasan {
        $report = $this->createReport($reporter, $kategori, $urgency);

        return Penugasan::create([
            'pengaduan_id' => $report->id,
            'teknisi_id' => $teknisi->id,
            'admin_id' => $admin->id,
            'status' => 'ditugaskan',
        ]);
    }

    private function createReport(User $reporter, Kategori $kategori, string $urgency): Pengaduan
    {
        return Pengaduan::create([
            'user_id' => $reporter->id,
            'kategori_id' => $kategori->id,
            'judul' => 'Kerusakan sarana sekolah',
            'deskripsi' => 'Terdapat kerusakan pada fasilitas yang perlu segera diperiksa.',
            'lokasi_kejadian' => 'Gedung Utama',
            'urgency' => $urgency,
        ]);
    }
}
