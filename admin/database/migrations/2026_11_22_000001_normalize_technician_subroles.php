<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['teknisi', 'guru_bk'])
            ->where('bidang', 'fasilitas')
            ->update(['bidang' => 'lapangan']);

        DB::table('users')
            ->whereIn('role', ['teknisi', 'guru_bk'])
            ->where('bidang', 'etik')
            ->update(['bidang' => 'konseling']);

        DB::table('users')
            ->where('role', 'guru_bk')
            ->update([
                'role' => 'teknisi',
                'bidang' => 'konseling',
            ]);

        DB::table('users')
            ->where('role', 'teknisi')
            ->where(function ($query) {
                $query->whereNull('bidang')->orWhere('bidang', '');
            })
            ->update(['bidang' => 'lapangan']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('bidang', 50)->default('lapangan')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'teknisi')
            ->where('bidang', 'lapangan')
            ->update(['bidang' => 'fasilitas']);

        DB::table('users')
            ->where('role', 'teknisi')
            ->where('bidang', 'konseling')
            ->update(['bidang' => 'etik']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('bidang', 50)->default('fasilitas')->change();
        });
    }
};
