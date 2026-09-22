<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the oldest assignment for each report and remove accidental duplicates.
        $duplicateGroups = DB::table('penugasan')
            ->select('pengaduan_id')
            ->groupBy('pengaduan_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $keepId = DB::table('penugasan')
                ->where('pengaduan_id', $group->pengaduan_id)
                ->orderBy('id')
                ->value('id');

            DB::table('penugasan')
                ->where('pengaduan_id', $group->pengaduan_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('penugasan', function (Blueprint $table) {
            $table->unique('pengaduan_id', 'penugasan_pengaduan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('penugasan', function (Blueprint $table) {
            $table->dropUnique('penugasan_pengaduan_unique');
        });
    }
};
