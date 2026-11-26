<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penugasan', function (Blueprint $table) {
            $table->enum('status', ['ditugaskan', 'diterima', 'menunggu_konfirmasi', 'selesai', 'ditolak'])
                ->default('ditugaskan')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('penugasan')
            ->where('status', 'menunggu_konfirmasi')
            ->update(['status' => 'diterima']);

        Schema::table('penugasan', function (Blueprint $table) {
            $table->enum('status', ['ditugaskan', 'diterima', 'selesai', 'ditolak'])
                ->default('ditugaskan')
                ->change();
        });
    }
};
