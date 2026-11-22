<?php

namespace App\Http\Controllers\Api;

use App\Events\NotifikasiDikirim;
use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'data' => $query->get(),
        ]);
    }

    /**
     * Get single report detail
     */
    public function show($id)
    {
        $pengaduan = Pengaduan::with(['kategori', 'fasilitas', 'pelapor', 'penugasans.logPengerjaans'])->find($id);

        if (! $pengaduan) {
            return response()->json(['success' => false, 'message' => 'Laporan tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pengaduan,
        ]);
    }

    /**
     * Store a new report from a student.
     */
    public function store(Request $request)
    {
        // Validasi Konten (Masalah #2)
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'kategori_id' => 'required|integer|exists:kategori,id',
            'fasilitas_id' => 'nullable|integer',
            'judul' => ['required', 'string', 'min:10', 'max:150', function ($attribute, $value, $fail) {
                if (! self::isMeaningfulText($value, 0.5)) {
                    $fail('Judul harus berupa kata yang jelas, tidak boleh hanya angka atau simbol acak.');
                }
            }],
            'deskripsi' => ['required', 'string', 'min:30', function ($attribute, $value, $fail) {
                if (! self::isMeaningfulText($value, 0.3)) {
                    $fail('Deskripsi harus berupa kalimat yang jelas, tidak boleh hanya angka atau simbol acak.');
                }
            }],
            'lokasi_kejadian' => ['required', 'string', 'min:3', 'max:150', function ($attribute, $value, $fail) {
                if (! self::isMeaningfulLocation($value)) {
                    $fail('Lokasi insiden harus berupa keterangan lokasi yang jelas.');
                }
            }],
            'urgency' => 'nullable|in:urgent,medium,low',
            'is_anonim' => 'boolean',
            'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // max 5MB (Masalah #3)
        ], [
            'judul.min' => 'Judul laporan minimal 10 karakter.',
            'deskripsi.min' => 'Mohon jelaskan kronologi kejadian dengan detail (minimal 30 karakter).',
            'lokasi_kejadian.required' => 'Lokasi insiden wajib dicantumkan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $isFacilityReport = Kategori::whereKey($request->kategori_id)->value('jenis') === 'fasilitas';

        // Anti-Spam (Masalah #1)
        $judul = trim($request->judul);
        $lokasi = trim($request->lokasi_kejadian);
        $deskripsi = trim($request->deskripsi);

        // Duplikat penuh: judul + lokasi + deskripsi identik, kapan pun & status apa pun
        $exactDuplicate = Pengaduan::where('user_id', $request->user_id)
            ->where('judul', $judul)
            ->where('lokasi_kejadian', $lokasi)
            ->where('deskripsi', $deskripsi)
            ->exists();

        if ($exactDuplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melaporkan insiden ini sebelumnya. Laporan tidak dapat dikirim berulang kali.',
            ], 429);
        }

        // Laporan serupa (judul & lokasi sama) dalam 24 jam terakhir yang belum selesai
        $recentSimilar = Pengaduan::where('user_id', $request->user_id)
            ->where('judul', $judul)
            ->where('lokasi_kejadian', $lokasi)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->whereIn('status', ['menunggu', 'diproses'])
            ->exists();

        if ($recentSimilar) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melaporkan insiden ini sebelumnya. Laporan Anda sedang dalam antrean kami.',
            ], 429);
        }

        // Upload Lampiran Nyata (Masalah #3)
        $fileUrl = null;
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $path = $file->store('bukti', 'public');
            $fileUrl = Storage::url($path);
        }

        $pengaduan = Pengaduan::create([
            'user_id' => $request->user_id,
            'kategori_id' => $request->kategori_id,
            'fasilitas_id' => $request->fasilitas_id,
            'judul' => trim($request->judul),
            'deskripsi' => trim($request->deskripsi),
            'lokasi_kejadian' => trim($request->lokasi_kejadian),
            'urgency' => $isFacilityReport ? 'medium' : $request->input('urgency', 'medium'),
            'status' => 'menunggu',
            'is_anonim' => $request->is_anonim == 'true' || $request->is_anonim == '1' || $request->is_anonim === true,
            'file_bukti_url' => $fileUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim.',
            'data' => $pengaduan->load(['kategori', 'fasilitas']),
        ], 201);
    }

    /**
     * Submit rating for a completed report.
     */
    public function submitRating(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating_kepuasan' => 'required|integer|min:1|max:5',
            'ulasan_pelapor' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengaduan = Pengaduan::find($id);

        if (! $pengaduan) {
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
            'data' => $pengaduan,
        ]);
    }

    /**
     * Reject a report by Admin.
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'alasan_penolakan' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengaduan = Pengaduan::find($id);

        if (! $pengaduan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        if ($pengaduan->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan yang sudah selesai tidak dapat ditolak.',
            ], 409);
        }

        if ($pengaduan->status === 'ditolak') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah ditolak sebelumnya.',
            ], 409);
        }

        $reason = trim($request->input('alasan_penolakan') ?? $request->input('catatan_admin') ?? '');

        $pengaduan->status = 'ditolak';
        $pengaduan->alasan_penolakan = $reason ?: null;
        if (! $pengaduan->completed_at) {
            $pengaduan->completed_at = Carbon::now();
        }
        $pengaduan->save();

        // Buat notifikasi penolakan ke pelapor
        $notif = Notifikasi::create([
            'user_id' => $pengaduan->user_id,
            'pengaduan_id' => $pengaduan->id,
            'type' => 'danger',
            'judul' => 'Laporan Ditolak',
            'pesan' => "Laporan Anda mengenai '{$pengaduan->judul}' telah ditolak oleh Admin." . ($reason ? " Alasan: {$reason}" : ''),
        ]);

        try {
            broadcast(new NotifikasiDikirim($notif));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil ditolak.',
            'data' => $pengaduan->load(['kategori', 'fasilitas', 'pelapor']),
        ]);
    }

    /**
     * Cek apakah teks benar-benar bermakna (bukan angka/simbol acak).
     * Minimal proporsi huruf [minLetterRatio] dan minimal 2 kata berhuruf (>= 3 huruf).
     */
    private static function isMeaningfulText(string $value, float $minLetterRatio): bool
    {
        $text = mb_strtolower(trim($value));
        if ($text === '') {
            return false;
        }

        $noSpaces = preg_replace('/\s+/', '', $text);
        $length = mb_strlen($noSpaces);
        if ($length === 0) {
            return false;
        }

        $letterCount = preg_match_all('/\p{L}/u', $text);
        if (($letterCount / $length) < $minLetterRatio) {
            return false;
        }

        $letterWords = array_filter(
            preg_split('/\s+/', $text),
            fn ($w) => (bool) preg_match('/\p{L}{3,}/u', $w)
        );

        return count($letterWords) >= 2;
    }

    private static function isMeaningfulLocation(string $value): bool
    {
        $text = trim($value);
        $letters = preg_match_all('/\p{L}/u', $text);
        $compactLength = mb_strlen(preg_replace('/\s+/', '', $text));

        if ($compactLength === 0 || $letters < 3 || ($letters / $compactLength) < 0.25) {
            return false;
        }

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 2) {
            return false;
        }

        return (bool) preg_match('/\p{L}{3,}/u', $text);
    }
}
