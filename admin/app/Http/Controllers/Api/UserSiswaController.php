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
     * Tampilkan daftar seluruh siswa.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'siswa')
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
     * Simpan akun siswa baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'     => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/u'],
            'email'    => ['required', 'string', 'regex:/^[0-9]{10}$/', 'unique:users,email'],
            'password' => 'required|string|min:6',
        ], [
            'nama.required'     => 'Nama siswa wajib diisi.',
            'nama.regex'        => 'Nama siswa hanya boleh berisi huruf.',
            'email.required'    => 'NISN wajib diisi.',
            'email.regex'       => 'NISN harus terdiri dari tepat 10 angka.',
            'email.unique'      => 'NISN ini sudah digunakan oleh akun lain.',
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
            'message' => 'Akun siswa berhasil ditambahkan.',
            'data'    => $user->loadCount('pengaduans'),
        ], 201);
    }

    /**
     * Detail akun siswa.
     */
    public function show($id)
    {
        $user = User::where('role', 'siswa')
            ->withCount('pengaduans')
            ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * Perbarui data siswa.
     */
    public function update(Request $request, $id)
    {
        $user = User::where('role', 'siswa')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'     => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/u'],
            'email'    => ['required', 'string', 'regex:/^[0-9]{10}$/', 'unique:users,email,' . $id],
            'kelas'    => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6',
        ], [
            'nama.required'     => 'Nama siswa wajib diisi.',
            'nama.regex'        => 'Nama siswa hanya boleh berisi huruf.',
            'email.required'    => 'NISN wajib diisi.',
            'email.regex'       => 'NISN harus terdiri dari tepat 10 angka.',
            'email.unique'      => 'NISN ini sudah digunakan oleh akun lain.',
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
        $user->nisn  = trim($request->email);
        if ($request->filled('kelas')) {
            $user->kelas = trim($request->kelas);
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil diperbarui.',
            'data'    => $user->loadCount('pengaduans'),
        ]);
    }

    /**
     * Hapus data siswa.
     */
    public function destroy($id)
    {
        $user = User::where('role', 'siswa')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil dihapus.',
        ]);
    }
}
