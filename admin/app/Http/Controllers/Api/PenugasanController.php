<?php

namespace App\Http\Controllers\Api;

use App\Events\NotifikasiDikirim;
use App\Http\Controllers\Controller;
use App\Models\LogPengerjaan;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\Penugasan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PenugasanController extends Controller
{
    /**
     * Get a list of tasks for a specific technician.
     */
    public function index(Request $request)
    {
        $teknisiId = $request->query('teknisi_id');
        $validator = Validator::make($request->query(), [
            'urgency' => 'nullable|in:urgent,medium,low',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Penugasan::with([
            'pengaduan.pelapor', 
            'pengaduan.kategori',
            'pengaduan.fasilitas',
            'logPengerjaans'
        ])->orderByRaw(
            "CASE (SELECT urgency FROM pengaduan WHERE pengaduan.id = penugasan.pengaduan_id)
                WHEN 'urgent' THEN 0
                WHEN 'medium' THEN 1
                WHEN 'low' THEN 2
                ELSE 3
            END"
        )->orderBy('created_at', 'desc');

        if ($teknisiId) {
            $query->where('teknisi_id', $teknisiId);
        }

        if ($request->filled('urgency')) {
            $query->whereHas('pengaduan', function ($pengaduanQuery) use ($request) {
                $pengaduanQuery->where('urgency', $request->query('urgency'));
            });
        }

        $tasks = $query->get();
        $tasks->each(function (Penugasan $task) {
            $task->setAttribute('urgency', $task->pengaduan?->urgency);
        });

        return response()->json([
            'success' => true,
            'data' => $tasks
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

        $teknisi = User::where('id', $request->teknisi_id)
            ->where('role', 'teknisi')
            ->first();
        if (! $teknisi) {
            return response()->json([
                'success' => false,
                'message' => 'Teknisi yang dipilih tidak ditemukan atau bukan akun teknisi.',
            ], 422);
        }

        $pengaduan = Pengaduan::find($request->pengaduan_id);
        if (! $pengaduan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        if ($pengaduan->status !== 'menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah diverifikasi atau sedang diproses, sehingga tidak dapat didisposisikan lagi.',
            ], 409);
        }

        if (Penugasan::where('pengaduan_id', $pengaduan->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah memiliki penugasan teknisi.',
            ], 409);
        }

        $activeTaskCount = Penugasan::where('teknisi_id', $request->teknisi_id)
            ->whereIn('status', ['ditugaskan', 'diterima'])
            ->count();

        if ($activeTaskCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Teknisi ini sudah memiliki 5 tugas aktif. Pilih teknisi lain atau tunggu tugas selesai.',
            ], 422);
        }

        $admin = User::where('role', 'admin')->orderBy('id')->first();
        if (! $admin) {
            return response()->json([
                'success' => false,
                'message' => 'Akun admin tidak ditemukan. Penugasan tidak dapat dibuat.',
            ], 500);
        }

        try {
            $penugasan = Penugasan::create([
                'pengaduan_id' => $request->pengaduan_id,
                'teknisi_id' => $request->teknisi_id,
                'admin_id' => $admin->id,
                'status' => 'ditugaskan',
                'catatan_admin' => $request->catatan_admin
            ]);
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'penugasan_pengaduan_unique')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan ini baru saja didisposisikan oleh admin lain.',
                ], 409);
            }

            throw $exception;
        }
        
        $pengaduan->status = 'diproses';
            // Masalah #5: Timestamp verifikasi & assign
            if (!$pengaduan->verified_at) {
                $pengaduan->verified_at = Carbon::now();
            }
            $pengaduan->assigned_at = Carbon::now();
        $pengaduan->save();

            // Masalah #6: Buat Notifikasi & Broadcast
            $notif = Notifikasi::create([
                'user_id' => $pengaduan->user_id,
                'judul' => 'Laporan Diproses',
                'pesan' => "Laporan Anda mengenai '{$pengaduan->judul}' telah diverifikasi dan diteruskan ke teknisi.",
                'is_read' => false,
            ]);
            try {
                broadcast(new NotifikasiDikirim($notif));
            } catch (\Throwable $exception) {
                report($exception);
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
            'catatan_teknisi' => 'nullable|string',
            'photo' => $request->input('status') === 'selesai'
                ? 'required|image|max:5120'
                : 'nullable|image|max:5120',
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

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photo_proofs', 'public');
            if ($photoPath === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto bukti gagal disimpan. Silakan coba kembali.',
                ], 500);
            }
        }

        // Update task status
        $penugasan->status = $request->status;
        $penugasan->save();

        // Create log pengerjaan
        $statusLabel = [
            'diterima' => 'Teknisi telah menerima dan mengkonfirmasi tugas.',
            'selesai' => 'Teknisi menandai pekerjaan selesai.',
        ];
        $defaultNote = $statusLabel[$request->status] ?? 'Status diperbarui.';
        LogPengerjaan::create([
            'penugasan_id' => $penugasan->id,
            'catatan_teknisi' => $request->catatan_teknisi ?: $defaultNote,
            'photo_proof' => $photoPath,
        ]);

        // Also update parent Pengaduan status
        $pengaduan = Pengaduan::find($penugasan->pengaduan_id);
        if ($pengaduan) {
            if ($request->status === 'diterima') {
                $pengaduan->status = 'diproses';
                $pengaduan->worked_at = Carbon::now(); // Masalah #5: Timestamp mulai kerja

                // Masalah #6: Notifikasi teknisi bekerja
                $notif = Notifikasi::create([
                    'user_id' => $pengaduan->user_id,
                    'judul' => 'Teknisi Sedang Bekerja',
                    'pesan' => "Teknisi sedang menangani laporan Anda mengenai '{$pengaduan->judul}'.",
                    'is_read' => false,
                ]);
                try {
                    broadcast(new NotifikasiDikirim($notif));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            } else if ($request->status === 'selesai') {
                $pengaduan->status = 'selesai';
                $pengaduan->completed_at = Carbon::now(); // Masalah #5: Timestamp selesai

                // Masalah #6: Notifikasi selesai
                $notif = Notifikasi::create([
                    'user_id' => $pengaduan->user_id,
                    'judul' => 'Laporan Selesai',
                    'pesan' => "Laporan Anda mengenai '{$pengaduan->judul}' telah diselesaikan. Terima kasih atas partisipasi Anda.",
                    'is_read' => false,
                ]);
                try {
                    broadcast(new NotifikasiDikirim($notif));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
            $pengaduan->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Log pengerjaan berhasil ditambahkan dan status diperbarui.',
            'data' => $penugasan
        ]);
    }

    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $penugasan = Penugasan::with('pengaduan')->find($id);
        if (! $penugasan) {
            return response()->json([
                'success' => false,
                'message' => 'Penugasan tidak ditemukan.',
            ], 404);
        }

        if ($penugasan->status !== 'ditugaskan') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya penugasan baru yang dapat ditolak.',
            ], 409);
        }

        $reason = trim($request->input('rejection_reason'));
        $penugasan->status = 'ditolak';
        $penugasan->rejection_reason = $reason;
        $penugasan->save();

        LogPengerjaan::create([
            'penugasan_id' => $penugasan->id,
            'catatan_teknisi' => 'Penugasan ditolak: '.$reason,
        ]);

        if ($penugasan->pengaduan) {
            $penugasan->pengaduan->status = 'ditolak';
            $penugasan->pengaduan->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Penugasan berhasil ditolak.',
            'data' => $penugasan->load('logPengerjaans'),
        ]);
    }
}
