<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasilitas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('lokasi', 100)->nullable();
            $table->string('status_kondisi')->default('Bisa Digunakan');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['fasilitas', 'etik']);
            $table->string('nama', 100);
            $table->timestamps();
        });

        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('kategori_id')->constrained('kategori')->onDelete('restrict');
            $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas')->onDelete('set null');
            $table->string('judul', 150);
            $table->text('deskripsi');
            $table->string('lokasi_kejadian', 150)->nullable();
            $table->string('foto_bukti', 255)->nullable();
            $table->enum('status', ['menunggu', 'diproses', 'selesai', 'ditolak'])->default('menunggu');
            $table->boolean('is_anonim')->default(false);
            $table->boolean('rating_kepuasan')->nullable();
            $table->text('ulasan_pelapor')->nullable();
            $table->timestamps();
        });

        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengaduan_id')->constrained('pengaduan')->onDelete('cascade');
            $table->foreignId('teknisi_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['ditugaskan', 'diterima', 'selesai'])->default('ditugaskan');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });

        Schema::create('log_pengerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->constrained('penugasan')->onDelete('cascade');
            $table->text('catatan_teknisi');
            $table->string('foto_hasil', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('judul', 100);
            $table->text('pesan');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            // Note: DB schema only showed created_at, but we'll add timestamps for consistency if needed, or just created_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('log_pengerjaan');
        Schema::dropIfExists('penugasan');
        Schema::dropIfExists('pengaduan');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('fasilitas');
    }
};
