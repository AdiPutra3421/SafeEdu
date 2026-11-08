<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->string('urgency', 16)->default('medium');
        });

        Schema::table('penugasan', function (Blueprint $table) {
            $table->enum('status', ['ditugaskan', 'diterima', 'selesai', 'ditolak'])
                ->default('ditugaskan')
                ->change();
            $table->text('rejection_reason')->nullable();
        });

        Schema::table('log_pengerjaan', function (Blueprint $table) {
            $table->string('photo_proof')->nullable();
        });
    }

    public function down(): void
    {
        DB::table('penugasan')->where('status', 'ditolak')->update(['status' => 'ditugaskan']);

        Schema::table('log_pengerjaan', function (Blueprint $table) {
            $table->dropColumn('photo_proof');
        });

        Schema::table('penugasan', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
            $table->enum('status', ['ditugaskan', 'diterima', 'selesai'])
                ->default('ditugaskan')
                ->change();
        });

        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropColumn('urgency');
        });
    }
};
