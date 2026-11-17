<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Add default users
        User::create([
            'nama' => 'Admin User',
            'email' => 'admin',
            'password' => bcrypt('admin'),
            'role' => 'admin'
        ]);

        User::create([
            'nama' => 'Teknisi User',
            'email' => 'teknisi',
            'password' => bcrypt('teknisi'),
            'role' => 'teknisi',
            'bidang' => 'fasilitas'
        ]);

        User::create([
            'nama' => 'Guru BK (Konseling)',
            'email' => 'gurubk',
            'password' => bcrypt('gurubk'),
            'role' => 'teknisi',
            'bidang' => 'etik'
        ]);

        User::create([
            'nama' => 'User SafeEdu',
            'email' => 'user',
            'password' => bcrypt('user'),
            'role' => 'siswa'
        ]);

        // Seed some master data
        $this->call(KategoriSeeder::class);

        \App\Models\Fasilitas::create(['nama' => 'Ruang Kelas 1A', 'lokasi' => 'Gedung A', 'status_kondisi' => 'Bisa Digunakan', 'deskripsi' => 'AC baru diganti bulan lalu.']);
        \App\Models\Fasilitas::create(['nama' => 'Laboratorium Komputer', 'lokasi' => 'Gedung B', 'status_kondisi' => 'Dalam Perbaikan', 'deskripsi' => '2 PC rusak pada motherboard.']);
    }
}
