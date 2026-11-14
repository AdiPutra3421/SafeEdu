<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        Kategori::firstOrCreate(
            ['jenis' => 'fasilitas'],
            ['nama' => 'Kerusakan Fasilitas'],
        );

        Kategori::firstOrCreate(
            ['jenis' => 'etik'],
            ['nama' => 'Pelanggaran Kode Etik'],
        );
    }
}
