<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_categories_for_the_user_app(): void
    {
        Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Kerusakan Fasilitas']);
        Kategori::create(['jenis' => 'etik', 'nama' => 'Pelanggaran Kode Etik']);

        $this->getJson('/api/kategori')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.jenis', 'fasilitas')
            ->assertJsonPath('data.1.jenis', 'etik');
    }

    public function test_it_rejects_a_report_with_a_missing_category(): void
    {
        $this->postJson('/api/pengaduan', [
            'user_id' => 1,
            'kategori_id' => 2,
            'judul' => 'AC Sedang Mati',
            'deskripsi' => 'AC sedang mati yang menyebabkan lantai menjadi licin dan ruangan menjadi panas',
            'lokasi_kejadian' => 'Gedung Aula',
            'urgency' => 'medium',
            'is_anonim' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('kategori_id');
    }

    public function test_it_accepts_report_details_with_normal_punctuation(): void
    {
        $reporter = User::factory()->create(['role' => 'siswa']);
        Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Kerusakan Fasilitas']);

        $this->postJson('/api/pengaduan', [
            'user_id' => $reporter->id,
            'kategori_id' => 1,
            'judul' => 'AC Lab 2 rusak, tolong segera diperbaiki!',
            'deskripsi' => 'AC di laboratorium 2 rusak, suhu naik cepat, dan ventilasi tidak berfungsi dengan baik. Mohon segera diperiksa.',
            'lokasi_kejadian' => 'Gedung Utama, Lt. 2, Laboratorium 2',
            'urgency' => 'urgent',
            'is_anonim' => false,
        ])->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_it_rejects_a_report_with_a_missing_reporter_or_facility(): void
    {
        $reporter = User::factory()->create(['role' => 'siswa']);
        Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Kerusakan Fasilitas']);
        $payload = [
            'user_id' => $reporter->id,
            'kategori_id' => 1,
            'judul' => 'AC Lab 2 rusak, mohon segera diperbaiki!',
            'deskripsi' => 'AC di laboratorium 2 rusak, suhu ruangan meningkat, dan ventilasi juga tidak berfungsi.',
            'lokasi_kejadian' => 'Gedung Utama, Laboratorium 2',
            'fasilitas_id' => 999,
        ];

        $this->postJson('/api/pengaduan', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fasilitas_id');

        $payload['user_id'] = 999;
        unset($payload['fasilitas_id']);

        $this->postJson('/api/pengaduan', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }
}
