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
        $userId = $request->query('user_id');

        $query = Pengaduan::with(['kategori', 'fasilitas', 'pelapor', 'penugasans'])->orderBy('created_at', 'desc');
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * Store a new report from a student.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'kategori_id' => 'required|integer',
            'fasilitas_id' => 'nullable|integer',
            'judul' => 'required|string|max:150',
            'deskripsi' => 'required|string',
            'lokasi_kejadian' => 'nullable|string|max:150',
            'is_anonim' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pengaduan = Pengaduan::create([
            'user_id' => $request->user_id,
            'kategori_id' => $request->kategori_id,
            'fasilitas_id' => $request->fasilitas_id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'lokasi_kejadian' => $request->lokasi_kejadian,
            'status' => 'menunggu',
            'is_anonim' => $request->is_anonim ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim.',
            'data' => $pengaduan
        ], 201);
    }

    /**
     * Submit rating for a completed report.
     */
    public function submitRating(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating_kepuasan' => 'required|integer|min:1|max:5',
            'ulasan_pelapor' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pengaduan = Pengaduan::find($id);

        if (!$pengaduan) {
            return response()->json(['success' => false, 'message' => 'Laporan tidak ditemukan'], 404);
        }

        if ($pengaduan->status !== 'selesai') {
            return response()->json(['success' => false, 'message' => 'Laporan belum selesai'], 400);
        }

        $pengaduan->rating_kepuasan = $request->rating_kepuasan;
        $pengaduan->ulasan_pelapor = $request->ulasan_pelapor;
        $pengaduan->save();

        return response()->json([
            'success' => true,
            'message' => 'Rating berhasil dikirim.',
            'data' => $pengaduan
        ]);
    }
}
