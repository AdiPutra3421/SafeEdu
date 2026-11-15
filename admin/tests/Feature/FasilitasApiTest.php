<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FasilitasApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_updates_a_facility(): void
    {
        $this->postJson('/api/fasilitas', [
            'nama' => 'Laboratorium Komputer',
            'lokasi' => 'Gedung B',
            'status_kondisi' => 'Bisa Digunakan',
            'deskripsi' => 'Ruang praktik komputer.',
        ])->assertCreated()
            ->assertJsonPath('data.nama', 'Laboratorium Komputer');

        $this->putJson('/api/fasilitas/1', [
            'status_kondisi' => 'Dalam Perbaikan',
        ])->assertOk()
            ->assertJsonPath('data.status_kondisi', 'Dalam Perbaikan');

        $this->assertDatabaseHas('fasilitas', [
            'id' => 1,
            'status_kondisi' => 'Dalam Perbaikan',
        ]);
    }

    public function test_it_rejects_an_unknown_facility_condition(): void
    {
        $this->postJson('/api/fasilitas', [
            'nama' => 'Laboratorium Komputer',
            'status_kondisi' => 'Unknown',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status_kondisi');
    }
}
