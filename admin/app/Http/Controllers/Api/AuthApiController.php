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
     * Login API untuk aplikasi Flutter (User & Teknisi).
     * Menerima email dan password, mengembalikan data user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
            'role' => 'nullable|string|in:siswa,teknisi,admin,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cari user berdasarkan email dan role jika ditentukan
        $loginInput = trim($request->email);
        $query = User::where('email', $loginInput);

        if ($request->filled('role')) {
            $role = $request->role === 'user' ? 'siswa' : $request->role;
            if ($role === 'teknisi') {
                $query->whereIn('role', ['teknisi', 'guru_bk']);
            } else {
                $query->where('role', $role);
            }
        } else {
            $query->whereIn('role', ['siswa', 'user', 'teknisi', 'guru_bk']);
        }
        $user = $query->first();

        if (! $user) {
            $notFoundMsg = ($request->role === 'teknisi')
                ? 'Akun teknisi tidak ditemukan.'
                : 'Email belum terdaftar. Silakan daftar terlebih dahulu.';

            return response()->json([
                'success' => false,
                'message' => $notFoundMsg,
            ], 404);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi salah. Silakan coba lagi.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->display_email,
                'role' => $user->role,
                'bidang' => $user->bidang,
            ],
        ]);
    }

    /**
     * Registrasi akun user baru dari user_app.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.regex' => 'Nama hanya boleh berisi huruf.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau langsung login.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nama' => trim($request->nama),
            'email' => trim($request->email),
            'password' => Hash::make($request->password),
            'role' => 'siswa',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil.',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->display_email,
                'role' => $user->role,
                'bidang' => $user->bidang,
            ],
        ], 201);
    }

    /**
     * Cek apakah sesi login masih valid (user masih ada di DB).
     */
    public function me(Request $request)
    {
        $userId = $request->query('user_id');
        $user = User::where('id', $userId)->whereIn('role', ['siswa', 'user', 'teknisi', 'guru_bk'])->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Data user tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->display_email,
                'role' => $user->role,
                'bidang' => $user->bidang,
            ],
        ]);
    }

    /**
     * Update profil user dari user_app.
     */
    public function updateProfile(Request $request)
    {
        $userId = $request->input('user_id', $request->query('user_id'));
        $user = User::where('id', $userId)->whereIn('role', ['siswa', 'user', 'teknisi', 'guru_bk'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'current_password' => 'required_with:password|string',
            'password' => 'nullable|string|min:6',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.regex' => 'Nama hanya boleh berisi huruf.',
            'current_password.required_with' => 'Kata sandi saat ini wajib diisi untuk mengganti kata sandi.',
            'password.min' => 'Password baru minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kata sandi saat ini tidak sesuai.',
                    'errors' => ['current_password' => ['Kata sandi saat ini tidak sesuai.']],
                ], 422);
            }
            $user->password = Hash::make($request->password);
        }

        $user->nama = trim($request->nama);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->display_email,
                'role' => $user->role,
                'bidang' => $user->bidang,
            ],
        ]);
    }

    /**
     * Logout API untuk aplikasi mobile (User & Teknisi).
     */
    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
