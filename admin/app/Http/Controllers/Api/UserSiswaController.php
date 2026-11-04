<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserSiswaController extends Controller
{
    /**
     * Tampilkan daftar seluruh user.
     */
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['siswa', 'user'])
            ->withCount('pengaduans')
            ->orderBy('id', 'desc');

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get(),
        ]);
    }

    /**
     * Simpan akun user baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'     => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => 'required|string|min:6',
        ], [
            'nama.required'     => 'Nama wajib diisi.',
            'nama.regex'        => 'Nama hanya boleh berisi huruf.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah digunakan oleh akun lain.',
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
            'message' => 'Akun user berhasil ditambahkan.',
            'data'    => $user->loadCount('pengaduans'),
        ], 201);
    }

    /**
     * Detail akun user.
     */
    public function show($id)
    {
        $user = User::whereIn('role', ['siswa', 'user'])
            ->withCount('pengaduans')
            ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * Perbarui data user.
     */
    public function update(Request $request, $id)
    {
        $user = User::whereIn('role', ['siswa', 'user'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'     => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => 'nullable|string|min:6',
        ], [
            'nama.required'     => 'Nama wajib diisi.',
            'nama.regex'        => 'Nama hanya boleh berisi huruf.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah digunakan oleh akun lain.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user->nama  = trim($request->nama);
        $user->email = trim($request->email);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diperbarui.',
            'data'    => $user->loadCount('pengaduans'),
        ]);
    }

    /**
     * Hapus data user.
     */
    public function destroy($id)
    {
        $user = User::whereIn('role', ['siswa', 'user'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil dihapus.',
        ]);
    }
}
