<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengaduan') && ! Schema::hasColumn('pengaduan', 'alasan_penolakan')) {
            Schema::table('pengaduan', function (Blueprint $table) {
                $table->text('alasan_penolakan')->nullable()->after('status');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'bidang')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('bidang', 50)->default('fasilitas')->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengaduan') && Schema::hasColumn('pengaduan', 'alasan_penolakan')) {
            Schema::table('pengaduan', function (Blueprint $table) {
                $table->dropColumn('alasan_penolakan');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'bidang')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('bidang');
            });
        }
    }
};
