<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserTeknisiController extends Controller
{
    /**
     * Tampilkan daftar seluruh teknisi beserta statistik penugasan.
     */
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['teknisi', 'guru_bk'])
            ->withCount([
                'penugasansSebagaiTeknisi as penugasans_count',
                'penugasansSebagaiTeknisi as tugas_selesai_count' => function ($q) {
                    $q->where('status', 'selesai');
                },
                'penugasansSebagaiTeknisi as tugas_aktif_count' => function ($q) {
                    $q->whereIn('status', ['ditugaskan', 'diterima']);
                },
            ])
            ->orderBy('id', 'desc');

        if ($request->filled('bidang')) {
            $query->where('bidang', $request->bidang);
        }

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
     * Simpan akun teknisi baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'max:100', 'unique:users,email'],
            'password' => 'required|string|min:6',
            'bidang'   => 'nullable|string|in:fasilitas,etik',
        ], [
            'nama.required'     => 'Nama teknisi wajib diisi.',
            'email.required'    => 'Email / Username teknisi wajib diisi.',
            'email.unique'      => 'Email / Username ini sudah digunakan oleh akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $bidang = $request->input('bidang', 'fasilitas');
        $user = User::create([
            'nama'     => trim($request->nama),
            'email'    => trim($request->email),
            'password' => Hash::make($request->password),
            'role'     => $bidang === 'etik' ? 'guru_bk' : 'teknisi',
            'bidang'   => $bidang,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun teknisi berhasil ditambahkan.',
            'data'    => $user->loadCount('penugasansSebagaiTeknisi as penugasans_count'),
        ], 201);
    }

    /**
     * Detail akun teknisi.
     */
    public function show($id)
    {
        $user = User::whereIn('role', ['teknisi', 'guru_bk'])
            ->withCount([
                'penugasansSebagaiTeknisi as penugasans_count',
                'penugasansSebagaiTeknisi as tugas_selesai_count' => function ($q) {
                    $q->where('status', 'selesai');
                },
            ])
            ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data teknisi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * Perbarui data akun teknisi.
     */
    public function update(Request $request, $id)
    {
        $user = User::whereIn('role', ['teknisi', 'guru_bk'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data teknisi tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'max:100', 'unique:users,email,' . $id],
            'password' => 'nullable|string|min:6',
            'bidang'   => 'nullable|string|in:fasilitas,etik',
        ], [
            'nama.required'  => 'Nama teknisi wajib diisi.',
            'email.required' => 'Email / Username teknisi wajib diisi.',
            'email.unique'   => 'Email / Username ini sudah digunakan oleh akun lain.',
            'password.min'   => 'Password minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user->nama  = trim($request->nama);
        $user->email = trim($request->email);

        if ($request->filled('bidang')) {
            $user->bidang = $request->bidang;
            $user->role = $request->bidang === 'etik' ? 'guru_bk' : 'teknisi';
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Data teknisi berhasil diperbarui.',
            'data'    => $user->loadCount('penugasansSebagaiTeknisi as penugasans_count'),
        ]);
    }

    /**
     * Hapus data akun teknisi.
     */
    public function destroy($id)
    {
        $user = User::whereIn('role', ['teknisi', 'guru_bk'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data teknisi tidak ditemukan.',
            ], 404);
        }

        // Cek apakah teknisi memiliki penugasan yang masih aktif
        $hasActiveTasks = $user->penugasansSebagaiTeknisi()->where('status', '!=', 'selesai')->exists();
        if ($hasActiveTasks) {
            return response()->json([
                'success' => false,
                'message' => 'Teknisi ini masih memiliki penugasan yang sedang berjalan. Selesaikan atau alihkan tugas terlebih dahulu sebelum menghapus.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data teknisi berhasil dihapus.',
        ]);
    }
}
