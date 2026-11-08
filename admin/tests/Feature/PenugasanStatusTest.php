<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenugasanStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_and_technician_updates_are_reflected_in_the_report_api(): void
    {
        $student = User::create([
            'nama' => 'Siswa',
            'email' => 'siswa@example.test',
            'password' => 'password',
            'role' => 'siswa',
        ]);
        $admin = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $technician = User::create([
            'nama' => 'Teknisi',
            'email' => 'teknisi@example.test',
            'password' => 'password',
            'role' => 'teknisi',
        ]);
        $category = Kategori::create([
            'jenis' => 'fasilitas',
            'nama' => 'Fasilitas',
        ]);
        $report = Pengaduan::create([
            'user_id' => $student->id,
            'kategori_id' => $category->id,
            'judul' => 'Kerusakan fasilitas kelas',
            'deskripsi' => 'Fasilitas kelas mengalami kerusakan dan perlu diperbaiki.',
            'lokasi_kejadian' => 'Ruang kelas',
            'status' => 'menunggu',
        ]);

        $dispatchResponse = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $technician->id,
        ])->assertOk();

        $assignmentId = $dispatchResponse->json('data.id');
        $this->assertDatabaseHas('pengaduan', [
            'id' => $report->id,
            'status' => 'diproses',
        ]);
        $this->assertDatabaseHas('penugasan', [
            'id' => $assignmentId,
            'admin_id' => $admin->id,
            'status' => 'ditugaskan',
        ]);
        $this->postJson("/api/penugasan/{$assignmentId}/status", [
            'status' => 'diterima',
        ])->assertOk()
            ->assertJsonPath('data.status', 'diterima')
            ->assertJsonPath('data.pengaduan.status', 'diproses');

        $this->assertDatabaseHas('pengaduan', [
            'id' => $report->id,
            'status' => 'diproses',
        ]);
        $this->assertDatabaseHas('penugasan', [
            'id' => $assignmentId,
            'status' => 'diterima',
        ]);
        $this->getJson("/api/pengaduan?user_id={$student->id}")
            ->assertOk()
            ->assertJsonPath('data.0.status', 'diproses')
            ->assertJsonPath('data.0.penugasans.0.status', 'diterima');

        $this->postJson("/api/penugasan/{$assignmentId}/status", [
            'status' => 'selesai',
        ])->assertOk()
            ->assertJsonPath('data.status', 'selesai')
            ->assertJsonPath('data.pengaduan.status', 'selesai');

        $this->getJson("/api/pengaduan?user_id={$student->id}")
            ->assertOk()
            ->assertJsonPath('data.0.status', 'selesai');
        $this->getJson("/api/penugasan?teknisi_id={$technician->id}")
            ->assertOk()
            ->assertJsonPath('data.0.status', 'selesai');
        $this->getJson('/api/pengaduan')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'selesai');
    }
}
