<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthApiController extends Controller
{
    /**
     * Login API untuk aplikasi Flutter siswa.
     * Menerima email/NISN dan password, mengembalikan data user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string',
            'password' => 'required|string',
            'role'     => 'nullable|string|in:siswa,teknisi,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Cari user berdasarkan email atau nisn dan role jika ditentukan
        $loginInput = trim($request->email);
        $query = User::where(function ($q) use ($loginInput) {
            $q->where('email', $loginInput)
              ->orWhere('nisn', $loginInput);
        });
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        } else {
            $query->whereIn('role', ['siswa', 'teknisi']);
        }
        $user = $query->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->display_email,
                'nisn'  => $user->nisn,
                'kelas' => $user->kelas,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * Registrasi akun siswa baru dari user_app.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'     => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/u'],
            'email'    => ['required', 'string', 'regex:/^[0-9]{10}$/', 'unique:users,email'],
            'password' => 'required|string|min:6',
            'kelas'    => 'nullable|string|max:100',
        ], [
            'nama.required'     => 'Nama lengkap wajib diisi.',
            'nama.regex'        => 'Nama hanya boleh berisi huruf.',
            'email.required'    => 'NISN wajib diisi.',
            'email.regex'       => 'NISN harus terdiri dari tepat 10 angka.',
            'email.unique'      => 'NISN ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nama'     => trim($request->nama),
            'email'    => trim($request->email),
            'nisn'     => trim($request->email),
            'kelas'    => $request->filled('kelas') ? trim($request->kelas) : 'XII Rekayasa Perangkat Lunak 1',
            'password' => Hash::make($request->password),
            'role'     => 'siswa',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil.',
            'data'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->display_email,
                'nisn'  => $user->nisn,
                'kelas' => $user->kelas,
                'role'  => $user->role,
            ],
        ], 201);
    }

    /**
     * Cek apakah sesi login masih valid (user masih ada di DB).
     */
    public function me(Request $request)
    {
        $userId = $request->query('user_id');
        $user = User::where('id', $userId)->where('role', 'siswa')->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->display_email,
                'nisn'  => $user->nisn,
                'kelas' => $user->kelas,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * Update profil siswa dari user_app.
     */
    public function updateProfile(Request $request)
    {
        $userId = $request->input('user_id', $request->query('user_id'));
        $user = User::where('id', $userId)->where('role', 'siswa')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'             => ['required', 'string', 'max:100', 'regex:/^[\pL\s\.\,\'\-]+$/u'],
            'kelas'            => 'nullable|string|max:100',
            'current_password' => 'nullable|string',
            'password'         => 'nullable|string|min:6',
        ], [
            'nama.required'    => 'Nama lengkap wajib diisi.',
            'nama.regex'       => 'Nama hanya boleh berisi huruf dan tanda baca umum.',
            'password.min'     => 'Password baru minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->filled('password')) {
            if ($request->filled('current_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kata sandi saat ini tidak sesuai.',
                        'errors'  => ['current_password' => ['Kata sandi saat ini tidak sesuai.']],
                    ], 422);
                }
            }
            $user->password = Hash::make($request->password);
        }

        $user->nama = trim($request->nama);
        if ($request->has('kelas')) {
            $user->kelas = trim($request->kelas);
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->display_email,
                'nisn'  => $user->nisn,
                'kelas' => $user->kelas,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * Logout API untuk aplikasi mobile (Siswa & Teknisi).
     */
    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
