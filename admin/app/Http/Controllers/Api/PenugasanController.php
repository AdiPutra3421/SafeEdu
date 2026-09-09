<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\StatusPengerjaan;
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
        // For testing, we mock technician ID if not provided
        $teknisiId = $request->query('teknisi_id', 2);

        $penugasan = Penugasan::with([
            'pengaduan.user', 
            'pengaduan.kategori',
            'statusPengerjaan'
        ])
            ->where('id_teknisi', $teknisiId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $penugasan
        ]);
    }

    /**
     * Update the status of a task by technician.
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_penugasan' => 'required|in:diterima,selesai',
            'keterangan_teknisi' => 'nullable|string',
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
        $penugasan->status_penugasan = $request->status_penugasan;
        $penugasan->save();

        // Save technician notes into status_pengerjaan table
        $mappedStatus = $request->status_penugasan === 'selesai' ? 'selesai dikerjakan' : 'dalam pengerjaan';

        StatusPengerjaan::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'status' => $mappedStatus,
            'keterangan' => $request->keterangan_teknisi,
            'tanggal_update' => now(),
        ]);

        // Also update parent Pengaduan status
        $pengaduan = Pengaduan::find($penugasan->id_pengaduan);
        if ($pengaduan) {
            if ($request->status_penugasan === 'diterima') {
                $pengaduan->status = 'dalam pengerjaan';
            } else if ($request->status_penugasan === 'selesai') {
                $pengaduan->status = 'selesai';
                $pengaduan->tanggal_selesai = now();
            }
            $pengaduan->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Status penugasan berhasil diperbarui.',
            'data' => $penugasan
        ]);
    }
}
