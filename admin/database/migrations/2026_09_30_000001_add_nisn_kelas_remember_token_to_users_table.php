<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nisn')) {
                $table->string('nisn', 20)->nullable()->after('nama');
            }
            if (!Schema::hasColumn('users', 'kelas')) {
                $table->string('kelas', 100)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });

        // Sinkronisasi data siswa yang ada: jika email 10 digit, set nisn = email, dan set kelas default
        $users = DB::table('users')->where('role', 'siswa')->get();
        foreach ($users as $u) {
            $updates = [];
            if (empty($u->nisn) && preg_match('/^[0-9]{10}$/', (string)$u->email)) {
                $updates['nisn'] = $u->email;
            }
            if (empty($u->kelas)) {
                $updates['kelas'] = 'XII Rekayasa Perangkat Lunak 1';
            }
            if (!empty($updates)) {
                DB::table('users')->where('id', $u->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nisn')) {
                $table->dropColumn('nisn');
            }
            if (Schema::hasColumn('users', 'kelas')) {
                $table->dropColumn('kelas');
            }
            if (Schema::hasColumn('users', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
    }
};
