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
    }

    public function down(): void
    {
        // Keep the column if it was created by the earlier rejection migration.
    }
};
