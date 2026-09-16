<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
$admin = User::find(1); $admin->email = 'admin'; $admin->password = Hash::make('admin'); $admin->save();
$teknisi = User::find(2); $teknisi->email = 'teknisi'; $teknisi->password = Hash::make('teknisi'); $teknisi->save();
$siswa = User::find(3); $siswa->email = 'user'; $siswa->password = Hash::make('user'); $siswa->save();
echo "Done";
