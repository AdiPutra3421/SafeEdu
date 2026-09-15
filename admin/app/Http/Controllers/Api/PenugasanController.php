<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\LogPengerjaan;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PenugasanController extends Controller
{
    /**
     * Get a list of tasks for a specific technician.
     */
    public function index(Request $request)
    {
        $teknisiId = $request->query('teknisi_id', 2);

        $penugasan = Penugasan::with([
            'pengaduan.pelapor', 
            'pengaduan.kategori',
            'pengaduan.fasilitas',
            'logPengerjaans'
        ])
            ->where('teknisi_id', $teknisiId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $penugasan
        ]);
    }

    /**
     * Add log pengerjaan and update status.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pengaduan_id' => 'required|integer',
            'teknisi_id' => 'required|integer',
            'catatan_admin' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $penugasan = Penugasan::create([
            'pengaduan_id' => $request->pengaduan_id,
            'teknisi_id' => $request->teknisi_id,
            'admin_id' => 1, // hardcoded Admin ID for now
            'status' => 'ditugaskan',
            'catatan_admin' => $request->catatan_admin
        ]);
        
        $pengaduan = Pengaduan::find($request->pengaduan_id);
        if ($pengaduan) {
            $pengaduan->status = 'diproses';
            $pengaduan->save();
        }

        return response()->json([
            'success' => true,
            'data' => $penugasan
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:ditugaskan,diterima,selesai',
            'catatan_teknisi' => 'required|string',
            'foto_hasil' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $penugasan = Penugasan::find($id);

        if (!$penugasan) {
            return response()->json([
                'success' => false,
                'message' => 'Penugasan tidak ditemukan.'
            ], 404);
        }

        // Update task status
        $penugasan->status = $request->status;
        $penugasan->save();

        // Create log pengerjaan
        LogPengerjaan::create([
            'penugasan_id' => $penugasan->id,
            'catatan_teknisi' => $request->catatan_teknisi,
            'foto_hasil' => $request->foto_hasil,
        ]);

        // Also update parent Pengaduan status
        $pengaduan = Pengaduan::find($penugasan->pengaduan_id);
        if ($pengaduan) {
            if ($request->status === 'diterima') {
                $pengaduan->status = 'diproses';
            } else if ($request->status === 'selesai') {
                $pengaduan->status = 'selesai';
            }
            $pengaduan->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Log pengerjaan berhasil ditambahkan dan status diperbarui.',
            'data' => $penugasan
        ]);
    }
}
