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

            $teknisi = User::whereKey($teknisiId)
                ->whereIn('role', ['teknisi', 'guru_bk'])
                ->first();
            $subRole = $teknisi ? self::technicianSubRole($teknisi) : null;

            if ($subRole === null) {
                $query->whereRaw('1 = 0');
            } else {
                $categoryType = $subRole === 'lapangan' ? 'fasilitas' : 'etik';
                $query->whereHas('pengaduan.kategori', function ($categoryQuery) use ($categoryType) {
                    $categoryQuery->where('jenis', $categoryType);
                });
            }
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
            'catatan_admin' => 'nullable|string',
            'urgency' => 'nullable|in:urgent,medium,low',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $teknisi = User::where('id', $request->teknisi_id)
            ->whereIn('role', ['teknisi', 'guru_bk'])
            ->first();
        if (! $teknisi) {
            return response()->json([
                'success' => false,
                'message' => 'Petugas / Teknisi yang dipilih tidak ditemukan.',
            ], 422);
        }

        $pengaduan = Pengaduan::with('kategori')->find($request->pengaduan_id);
        if (! $pengaduan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        $isFacilityReport = $pengaduan->kategori?->jenis === 'fasilitas';
        $subRole = self::technicianSubRole($teknisi);
        $isCompatible = $isFacilityReport
            ? $subRole === 'lapangan'
            : $subRole === 'konseling';

        if (! $isCompatible) {
            return response()->json([
                'success' => false,
                'message' => $isFacilityReport
                    ? 'Laporan fasilitas hanya dapat ditugaskan ke teknisi sub-role Lapangan.'
                    : 'Laporan kode etik hanya dapat ditugaskan ke teknisi sub-role Konseling.',
            ], 422);
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
        if ($request->filled('urgency')) {
            $pengaduan->urgency = $request->input('urgency');
        }
        // Masalah #5: Timestamp verifikasi & assign
        if (! $pengaduan->verified_at) {
            $pengaduan->verified_at = Carbon::now();
        }
        $pengaduan->assigned_at = Carbon::now();
        $pengaduan->save();

            // Masalah #6: Buat Notifikasi & Broadcast
        $recipientRoleName = $subRole === 'konseling' ? 'tim konseling' : 'teknisi lapangan';
        $notif = Notifikasi::create([
            'user_id' => $pengaduan->user_id,
            'pengaduan_id' => $pengaduan->id,
            'type' => 'info',
            'judul' => 'Laporan Diproses',
            'pesan' => "Laporan Anda mengenai '{$pengaduan->judul}' telah diverifikasi dan diteruskan ke {$recipientRoleName}.",
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

        if ($penugasan->status === 'menunggu_konfirmasi') {
            return response()->json([
                'success' => false,
                'message' => 'Pekerjaan sedang menunggu konfirmasi admin.',
            ], 409);
        }

        if (in_array($penugasan->status, ['selesai', 'ditolak'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Status penugasan ini tidak dapat diperbarui lagi.',
            ], 409);
        }

        if ($request->status === 'selesai' && $penugasan->status !== 'diterima') {
            return response()->json([
                'success' => false,
                'message' => 'Tugas harus diterima sebelum dapat diselesaikan.',
            ], 409);
        }

        $pengaduan = Pengaduan::with('kategori')->find($penugasan->pengaduan_id);
        $teknisi = User::find($penugasan->teknisi_id);
        if (! self::canHandleReport($teknisi, $pengaduan)) {
            return response()->json([
                'success' => false,
                'message' => 'Sub-role teknisi tidak sesuai dengan kategori laporan ini.',
            ], 422);
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

        $penugasan->status = $request->status === 'selesai'
            ? 'menunggu_konfirmasi'
            : $request->status;
        if ($request->status === 'selesai') {
            $penugasan->rejection_reason = null;
        }
        $penugasan->save();

        // Create log pengerjaan
        $statusLabel = [
            'diterima' => 'Teknisi telah menerima dan mengkonfirmasi tugas.',
            'selesai' => 'Teknisi mengirim bukti penyelesaian dan menunggu konfirmasi admin.',
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
            } else if ($request->status === 'selesai') {
                $pengaduan->status = 'diproses';
                $pengaduan->completed_at = null;

                $notif = Notifikasi::create([
                    'user_id' => $pengaduan->user_id,
                    'pengaduan_id' => $pengaduan->id,
                    'type' => 'info',
                    'judul' => 'Menunggu Konfirmasi Admin',
                    'pesan' => "Teknisi telah mengirim bukti penyelesaian laporan '{$pengaduan->judul}'. Laporan menunggu konfirmasi admin.",
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
            'message' => $request->status === 'selesai'
                ? 'Bukti pekerjaan berhasil dikirim dan menunggu konfirmasi admin.'
                : 'Log pengerjaan berhasil ditambahkan dan status diperbarui.',
            'data' => $penugasan->load('logPengerjaans'),
        ]);
    }

    public function approveCompletion($id)
    {
        $notification = null;
        $response = DB::transaction(function () use ($id, &$notification) {
            $penugasan = Penugasan::with('pengaduan')
                ->lockForUpdate()
                ->find($id);

            if (! $penugasan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penugasan tidak ditemukan.',
                ], 404);
            }

            if ($penugasan->status !== 'menunggu_konfirmasi') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pekerjaan yang menunggu konfirmasi admin yang dapat disetujui.',
                ], 409);
            }

            if (! $penugasan->pengaduan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan untuk penugasan ini tidak ditemukan.',
                ], 404);
            }

            $penugasan->status = 'selesai';
            $penugasan->rejection_reason = null;
            $penugasan->save();

            $pengaduan = $penugasan->pengaduan;
            $pengaduan->status = 'selesai';
            $pengaduan->completed_at = Carbon::now();
            $pengaduan->save();

            $notification = Notifikasi::create([
                'user_id' => $pengaduan->user_id,
                'pengaduan_id' => $pengaduan->id,
                'type' => 'success',
                'judul' => 'Laporan Selesai',
                'pesan' => "Admin telah mengonfirmasi penyelesaian laporan '{$pengaduan->judul}'.",
            ]);

            $penugasan->setRelation('pengaduan', $pengaduan);

            return response()->json([
                'success' => true,
                'message' => 'Pekerjaan berhasil dikonfirmasi dan laporan ditutup.',
                'data' => $penugasan->load('logPengerjaans'),
            ]);
        });

        if ($notification) {
            try {
                broadcast(new NotifikasiDikirim($notification));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }

    public function rejectCompletion(Request $request, $id)
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

        $reason = trim($request->input('rejection_reason'));
        $notification = null;
        $response = DB::transaction(function () use ($id, $reason, &$notification) {
            $penugasan = Penugasan::with('pengaduan')
                ->lockForUpdate()
                ->find($id);

            if (! $penugasan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penugasan tidak ditemukan.',
                ], 404);
            }

            if ($penugasan->status !== 'menunggu_konfirmasi') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pekerjaan yang menunggu konfirmasi admin yang dapat ditolak.',
                ], 409);
            }

            if (! $penugasan->pengaduan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan untuk penugasan ini tidak ditemukan.',
                ], 404);
            }

            $penugasan->status = 'diterima';
            $penugasan->rejection_reason = $reason;
            $penugasan->save();

            LogPengerjaan::create([
                'penugasan_id' => $penugasan->id,
                'catatan_teknisi' => 'Admin meminta perbaikan: '.$reason,
            ]);

            $pengaduan = $penugasan->pengaduan;
            $pengaduan->status = 'diproses';
            $pengaduan->completed_at = null;
            $pengaduan->save();

            $notification = Notifikasi::create([
                'user_id' => $pengaduan->user_id,
                'pengaduan_id' => $pengaduan->id,
                'type' => 'warning',
                'judul' => 'Perlu Perbaikan Ulang',
                'pesan' => "Admin meminta teknisi memperbaiki kembali laporan '{$pengaduan->judul}'.",
            ]);

            $penugasan->setRelation('pengaduan', $pengaduan);

            return response()->json([
                'success' => true,
                'message' => 'Pekerjaan ditolak dan dikembalikan ke teknisi untuk diperbaiki.',
                'data' => $penugasan->load('logPengerjaans'),
            ]);
        });

        if ($notification) {
            try {
                broadcast(new NotifikasiDikirim($notification));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }

    private static function technicianSubRole(User $teknisi): ?string
    {
        if ($teknisi->role === 'guru_bk' || in_array($teknisi->bidang, ['konseling', 'etik'], true)) {
            return 'konseling';
        }

        if (in_array($teknisi->bidang, ['lapangan', 'fasilitas'], true)) {
            return 'lapangan';
        }

        return null;
    }

    private static function canHandleReport(?User $teknisi, ?Pengaduan $pengaduan): bool
    {
        if (! $teknisi || ! $pengaduan || ! in_array($teknisi->role, ['teknisi', 'guru_bk'], true)) {
            return false;
        }

        $subRole = self::technicianSubRole($teknisi);
        $isFacilityReport = $pengaduan->kategori?->jenis === 'fasilitas';

        return $isFacilityReport
            ? $subRole === 'lapangan'
            : $subRole === 'konseling';
    }
}
