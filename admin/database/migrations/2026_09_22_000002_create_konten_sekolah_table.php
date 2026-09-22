<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konten_sekolah', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['berita', 'panduan']);
            $table->string('judul', 180);
            $table->text('ringkasan')->nullable();
            $table->longText('isi');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konten_sekolah');
    }
};