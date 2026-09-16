<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['fasilitas', 'kategori', 'log_pengerjaan', 'notifikasi', 'pengaduan', 'penugasan', 'users'];
foreach ($tables as $table) {
    try {
        $result = DB::select('SHOW CREATE TABLE ' . $table);
        $key = 'Create Table';
        echo $result[0]->$key . ";\n\n";
    } catch (Exception $e) {
        echo "Error getting schema for $table\n";
    }
}
