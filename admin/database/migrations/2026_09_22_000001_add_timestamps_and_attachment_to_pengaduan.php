<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom timestamp tiap tahapan ke tabel pengaduan
        Schema::table('pengaduan', function (Blueprint $table) {
            // Timestamp saat laporan diverifikasi oleh admin (disposisi)
            $table->timestamp('verified_at')->nullable()->after('is_anonim');
            // Timestamp saat laporan diteruskan/ditugaskan ke teknisi
            $table->timestamp('assigned_at')->nullable()->after('verified_at');
            // Timestamp saat teknisi mulai mengerjakan (status diterima)
            $table->timestamp('worked_at')->nullable()->after('assigned_at');
            // Timestamp saat laporan selesai dikerjakan
            $table->timestamp('completed_at')->nullable()->after('worked_at');
            // Ganti foto_bukti menjadi file_bukti (path file upload)
            // Kolom foto_bukti sudah ada di schema awal, kita tambah file_bukti_url untuk URL publik
            $table->string('file_bukti_url', 500)->nullable()->after('completed_at');
        });

        // Tambah kolom pengaduan_id dan type ke tabel notifikasi
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->unsignedBigInteger('pengaduan_id')->nullable()->after('user_id');
            $table->string('type', 50)->default('info')->after('pengaduan_id'); // info, success, warning, danger
            // Tambah updated_at agar bisa menggunakan timestamps() penuh
            $table->timestamp('updated_at')->nullable()->after('is_read');

            $table->foreign('pengaduan_id')->references('id')->on('pengaduan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'assigned_at', 'worked_at', 'completed_at', 'file_bukti_url']);
        });

        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropForeign(['pengaduan_id']);
            $table->dropColumn(['pengaduan_id', 'type', 'updated_at']);
        });
    }
};
