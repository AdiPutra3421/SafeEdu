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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenugasanController extends Controller
{
    /**
     * Get a list of tasks for a specific technician.
     */
    public function index(Request $request)
    {
        $teknisiId = $request->query('teknisi_id');

        $query = Penugasan::with([
            'pengaduan.pelapor',
            'pengaduan.kategori',
            'pengaduan.fasilitas',
            'logPengerjaans',
        ])->orderBy('created_at', 'desc');

        if ($teknisiId) {
            $query->where('teknisi_id', $teknisiId);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
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
            'catatan_admin' => 'nullable|string',
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

        $admin = User::where('role', 'admin')->orderBy('id')->first();
        if (! $admin) {
            return response()->json([
                'success' => false,
                'message' => 'Akun admin tidak ditemukan. Penugasan tidak dapat dibuat.',
            ], 500);
        }

        try {
            $penugasan = DB::transaction(function () use ($request, $pengaduan, $admin) {
                $penugasan = Penugasan::create([
                    'pengaduan_id' => $request->pengaduan_id,
                    'teknisi_id' => $request->teknisi_id,
                    'admin_id' => $admin->id,
                    'status' => 'ditugaskan',
                    'catatan_admin' => $request->catatan_admin,
                ]);

                $pengaduan->status = 'diproses';
                if (! $pengaduan->verified_at) {
                    $pengaduan->verified_at = Carbon::now();
                }
                $pengaduan->assigned_at = Carbon::now();
                $pengaduan->save();

                $notif = Notifikasi::create([
                    'user_id' => $pengaduan->user_id,
                    'pengaduan_id' => $pengaduan->id,
                    'type' => 'info',
                    'judul' => 'Laporan Diproses',
                    'pesan' => "Laporan Anda mengenai '{$pengaduan->judul}' telah diverifikasi dan diteruskan ke teknisi.",
                ]);

                try {
                    broadcast(new NotifikasiDikirim($notif));
                } catch (\Throwable $exception) {
                    report($exception);
                }

                return $penugasan;
            });
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'penugasan_pengaduan_unique')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan ini baru saja didisposisikan oleh admin lain.',
                ], 409);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'data' => $penugasan,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:ditugaskan,diterima,selesai',
            'catatan_teknisi' => 'nullable|string',
            'foto_hasil' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $penugasan = Penugasan::find($id);

        if (! $penugasan) {
            return response()->json([
                'success' => false,
                'message' => 'Penugasan tidak ditemukan.',
            ], 404);
        }

        DB::transaction(function () use ($penugasan, $request) {
            $penugasan->status = $request->status;
            $penugasan->save();

            $statusLabel = [
                'diterima' => 'Teknisi telah menerima dan mengkonfirmasi tugas.',
                'selesai' => 'Teknisi menandai pekerjaan selesai.',
            ];
            $defaultNote = $statusLabel[$request->status] ?? 'Status diperbarui.';
            LogPengerjaan::create([
                'penugasan_id' => $penugasan->id,
                'catatan_teknisi' => $request->catatan_teknisi ?: $defaultNote,
                'foto_hasil' => $request->foto_hasil,
            ]);

            $pengaduan = Pengaduan::find($penugasan->pengaduan_id);
            if (! $pengaduan) {
                return;
            }

            if ($request->status === 'diterima') {
                $pengaduan->status = 'diproses';
                $pengaduan->worked_at = Carbon::now();

                $notif = Notifikasi::create([
                    'user_id' => $pengaduan->user_id,
                    'pengaduan_id' => $pengaduan->id,
                    'type' => 'warning',
                    'judul' => 'Teknisi Sedang Bekerja',
                    'pesan' => "Teknisi sedang menangani laporan Anda mengenai '{$pengaduan->judul}'.",
                ]);
                try {
                    broadcast(new NotifikasiDikirim($notif));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            } elseif ($request->status === 'selesai') {
                $pengaduan->status = 'selesai';
                $pengaduan->completed_at = Carbon::now();

                $notif = Notifikasi::create([
                    'user_id' => $pengaduan->user_id,
                    'pengaduan_id' => $pengaduan->id,
                    'type' => 'success',
                    'judul' => 'Laporan Selesai',
                    'pesan' => "Laporan Anda mengenai '{$pengaduan->judul}' telah diselesaikan. Terima kasih atas partisipasi Anda.",
                ]);
                try {
                    broadcast(new NotifikasiDikirim($notif));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
            $pengaduan->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Log pengerjaan berhasil ditambahkan dan status diperbarui.',
            'data' => $penugasan->load('pengaduan'),
        ]);
    }
}
