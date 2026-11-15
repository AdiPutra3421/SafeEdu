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

    public function test_it_accepts_normal_punctuation_in_report_text(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $kategori = Kategori::create(['jenis' => 'fasilitas', 'nama' => 'Fasilitas']);

        $this->postJson('/api/pengaduan', [
            'user_id' => $user->id,
            'kategori_id' => $kategori->id,
            'judul' => 'AC Lab 2 rusak!',
            'deskripsi' => 'AC di Lab 2 rusak, mohon segera diperiksa dan diperbaiki.',
            'lokasi_kejadian' => 'Gedung A, lantai 2.',
            'urgency' => 'medium',
            'is_anonim' => false,
        ])->assertCreated()
            ->assertJsonPath('data.judul', 'AC Lab 2 rusak!')
            ->assertJsonPath('data.lokasi_kejadian', 'Gedung A, lantai 2.');
    }
}
