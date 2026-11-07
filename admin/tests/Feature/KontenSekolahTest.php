<?php

namespace Tests\Feature;

use App\Models\KontenSekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KontenSekolahTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_news_with_valid_content(): void
    {
        $response = $this->postJson('/api/konten-sekolah', [
            'tipe' => 'berita',
            'judul' => 'Informasi Kegiatan Sekolah',
            'ringkasan' => 'Informasi singkat kegiatan sekolah untuk semua siswa.',
            'isi' => 'Sekolah akan mengadakan kegiatan bersama pada hari Jumat. Seluruh siswa diharapkan hadir tepat waktu.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.judul', 'Informasi Kegiatan Sekolah');

        $this->assertDatabaseHas('konten_sekolah', [
            'judul' => 'Informasi Kegiatan Sekolah',
        ]);
    }

    public function test_it_rejects_invalid_news_fields_and_script_content(): void
    {
        $validData = [
            'tipe' => 'berita',
            'judul' => 'Informasi Kegiatan Sekolah',
            'ringkasan' => 'Informasi singkat kegiatan sekolah untuk semua siswa.',
            'isi' => 'Sekolah akan mengadakan kegiatan bersama pada hari Jumat. Seluruh siswa diharapkan hadir tepat waktu.',
        ];

        $invalidCases = [
            ['judul' => 'Berita', 'field' => 'judul'],
            ['judul' => '1234567890', 'field' => 'judul'],
            ['judul' => 'asdfghjkl zxcvbnm', 'field' => 'judul'],
            ['judul' => 'Berita @ Sekolah', 'field' => 'judul'],
            ['gambar_url' => 'https://example.com/banner.gif', 'field' => 'gambar_url'],
            ['ringkasan' => 'Terlalu singkat', 'field' => 'ringkasan'],
            ['isi' => 'Konten yang terlalu singkat.', 'field' => 'isi'],
            ['isi' => 'asdfghjkl zxcvbnm asdfghjkl zxcvbnm asdfghjkl zxcvbnm', 'field' => 'isi'],
            ['isi' => 'Berita ini berisi teks <script>alert("x")</script> yang dilarang.', 'field' => 'isi'],
        ];

        foreach ($invalidCases as $invalidCase) {
            $payload = $validData;
            $field = $invalidCase['field'];
            $payload[$field] = $invalidCase[$field];

            $this->postJson('/api/konten-sekolah', $payload)
                ->assertUnprocessable()
                ->assertJsonStructure(['errors' => [$field]]);
        }
    }

    public function test_it_rejects_likely_random_guide_content(): void
    {
        $validData = [
            'tipe' => 'panduan',
            'judul' => 'Panduan Kegiatan Sekolah',
            'isi' => 'Panduan ini menjelaskan langkah kegiatan sekolah secara jelas dan mudah diikuti.',
        ];

        $this->postJson('/api/konten-sekolah', [
            ...$validData,
            'judul' => 'asdfghjkl zxcvbnm',
        ])->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['judul']]);

        $this->postJson('/api/konten-sekolah', [
            ...$validData,
            'isi' => 'asdfghjkl zxcvbnm asdfghjkl zxcvbnm asdfghjkl zxcvbnm',
        ])->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['isi']]);
    }

    public function test_it_rejects_a_title_used_by_another_content(): void
    {
        KontenSekolah::create([
            'tipe' => 'panduan',
            'judul' => 'Informasi Kegiatan Sekolah',
            'isi' => 'Isi panduan sekolah.',
        ]);

        $this->postJson('/api/konten-sekolah', [
            'tipe' => 'berita',
            'judul' => 'Informasi Kegiatan Sekolah',
            'isi' => 'Sekolah akan mengadakan kegiatan bersama pada hari Jumat. Seluruh siswa diharapkan hadir tepat waktu.',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.judul.0', 'Judul konten sudah digunakan.');
    }

    public function test_updating_content_to_inactive_hides_it_from_users(): void
    {
        $content = KontenSekolah::create([
            'tipe' => 'panduan',
            'judul' => 'Panduan Penggunaan Fasilitas',
            'isi' => 'Panduan penggunaan fasilitas sekolah.',
            'aktif' => true,
        ]);

        $this->postJson("/api/konten-sekolah/{$content->id}", [
            'tipe' => 'panduan',
            'judul' => 'Panduan Penggunaan Fasilitas',
            'isi' => 'Panduan penggunaan fasilitas sekolah.',
            'aktif' => '0',
        ])->assertOk()
            ->assertJsonPath('data.aktif', false);

        $this->getJson('/api/konten-sekolah')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/konten-sekolah?admin=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.aktif', false);
    }
}
