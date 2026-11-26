<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_technician_rejection_routes_are_removed(): void
    {
        $this->postJson('/api/penugasan/1/reject')->assertNotFound();
        $this->postJson('/api/tasks/1/reject')->assertNotFound();
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

    public function test_konseling_technician_completion_with_photo_waits_for_admin_approval(): void
    {
        Storage::fake('public');

        $teknisi = User::factory()->create([
            'role' => 'teknisi',
            'bidang' => 'konseling',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'etik', 'nama' => 'Kode Etik']);
        $task = $this->createTask($reporter, $teknisi, $admin, $kategori, 'medium');
        $task->update(['status' => 'diterima']);
        $task->pengaduan->update(['status' => 'diproses']);

        $this->post("/api/penugasan/{$task->id}/status", [
            'status' => 'selesai',
            'catatan_teknisi' => 'Tindak lanjut sudah dilakukan.',
            'photo' => UploadedFile::fake()->create('bukti.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.status', 'menunggu_konfirmasi');

        $this->assertDatabaseHas('penugasan', [
            'id' => $task->id,
            'status' => 'menunggu_konfirmasi',
        ]);
        $this->assertDatabaseHas('pengaduan', [
            'id' => $task->pengaduan_id,
            'status' => 'diproses',
            'completed_at' => null,
        ]);

        $proofPath = $task->fresh()->logPengerjaans()->latest()->value('photo_proof');
        $this->assertNotNull($proofPath);
        Storage::disk('public')->assertExists($proofPath);
    }

    public function test_admin_approval_closes_a_task_waiting_for_confirmation(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Fasilitas']);
        $task = $this->createTask($reporter, $teknisi, $admin, $kategori, 'medium');
        $task->update(['status' => 'menunggu_konfirmasi']);
        $task->pengaduan->update(['status' => 'diproses']);

        $this->postJson("/api/penugasan/{$task->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'selesai')
            ->assertJsonPath('data.pengaduan.status', 'selesai');

        $this->assertDatabaseHas('penugasan', [
            'id' => $task->id,
            'status' => 'selesai',
        ]);
        $this->assertDatabaseHas('pengaduan', [
            'id' => $task->pengaduan_id,
            'status' => 'selesai',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $reporter->id,
            'judul' => 'Laporan Selesai',
        ]);
    }

    public function test_admin_rejection_returns_completion_to_technician_with_reason(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Fasilitas']);
        $task = $this->createTask($reporter, $teknisi, $admin, $kategori, 'medium');
        $task->update(['status' => 'menunggu_konfirmasi']);
        $task->pengaduan->update(['status' => 'diproses']);

        $this->postJson("/api/penugasan/{$task->id}/reject-completion", [
            'rejection_reason' => 'Foto bukti belum menunjukkan hasil perbaikan.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'diterima')
            ->assertJsonPath('data.rejection_reason', 'Foto bukti belum menunjukkan hasil perbaikan.');

        $this->assertDatabaseHas('penugasan', [
            'id' => $task->id,
            'status' => 'diterima',
            'rejection_reason' => 'Foto bukti belum menunjukkan hasil perbaikan.',
        ]);
        $this->assertDatabaseHas('pengaduan', [
            'id' => $task->pengaduan_id,
            'status' => 'diproses',
            'completed_at' => null,
        ]);
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
