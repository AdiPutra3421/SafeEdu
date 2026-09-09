<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengaduanController extends Controller
{
    /**
     * Get a list of reports for a specific user (student).
     */
    public function index(Request $request)
    {
        // For testing, we mock user ID if not provided
        $userId = $request->query('user_id', 1);

        $pengaduan = Pengaduan::with(['kategori', 'fasilitas'])
            ->where('id_user', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pengaduan
        ]);
    }

    /**
     * Store a new report from a student.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer',
            'id_kategori' => 'required|integer',
            'id_fasilitas' => 'nullable|integer',
            'judul' => 'required|string|max:150',
            'isi_pengaduan' => 'required|string',
            'lokasi' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pengaduan = Pengaduan::create([
            'id_user' => $request->id_user,
            'id_kategori' => $request->id_kategori,
            'id_fasilitas' => $request->id_fasilitas,
            'judul' => $request->judul,
            'isi_pengaduan' => $request->isi_pengaduan,
            'lokasi' => $request->lokasi,
            'status' => 'menunggu',
            'tanggal_pengaduan' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim.',
            'data' => $pengaduan
        ], 201);
    }
}
