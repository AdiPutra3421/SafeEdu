<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /**
     * Ambil daftar notifikasi untuk user tertentu.
     * GET /api/notifikasi?user_id=X
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'user_id diperlukan.'], 422);
        }

        $notifikasis = Notifikasi::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $notifikasis,
        ]);
    }

    /**
     * Jumlah notifikasi belum dibaca (untuk badge icon).
     * GET /api/notifikasi/unread-count?user_id=X
     */
    public function unreadCount(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'user_id diperlukan.'], 422);
        }

        $count = Notifikasi::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }

    /**
     * Tandai notifikasi sudah dibaca.
     * POST /api/notifikasi/{id}/read
     */
    public function markRead(Request $request, $id)
    {
        $notif = Notifikasi::find($id);

        if (!$notif) {
            return response()->json(['success' => false, 'message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        $notif->is_read = true;
        $notif->save();

        return response()->json(['success' => true, 'message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    /**
     * Tandai semua notifikasi user sudah dibaca.
     * POST /api/notifikasi/read-all?user_id=X
     */
    public function markAllRead(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'user_id diperlukan.'], 422);
        }

        Notifikasi::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}
