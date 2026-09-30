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

        // Cari user berdasarkan email dan role jika ditentukan
        $query = User::where('email', $request->email);
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
                'email' => $user->email,
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
            'password' => Hash::make($request->password),
            'role'     => 'siswa',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil.',
            'data'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->email,
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
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }
}
