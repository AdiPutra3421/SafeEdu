<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KontenSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KontenSekolahController extends Controller
{
    public function index(Request $request)
    {
        $query = KontenSekolah::query()->latest();

        if (! $request->boolean('admin')) {
            $query->where('aktif', true);
        }

        if ($request->filled('tipe') && in_array($request->tipe, ['berita', 'panduan'], true)) {
            $query->where('tipe', $request->tipe);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateContent($request);
        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil ditambahkan.',
            'data' => KontenSekolah::create($validated),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $content = KontenSekolah::find($id);
        if (! $content) {
            return response()->json(['success' => false, 'message' => 'Konten tidak ditemukan.'], 404);
        }

        $validated = $this->validateContent($request);
        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        $content->update($validated);

        return response()->json(['success' => true, 'message' => 'Konten berhasil diperbarui.', 'data' => $content]);
    }

    public function destroy($id)
    {
        $content = KontenSekolah::find($id);
        if (! $content) {
            return response()->json(['success' => false, 'message' => 'Konten tidak ditemukan.'], 404);
        }

        $content->delete();

        return response()->json(['success' => true, 'message' => 'Konten berhasil dihapus.']);
    }

    private function validateContent(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipe' => 'required|in:berita,panduan',
            'judul' => 'required|string|max:180',
            'ringkasan' => 'nullable|string|max:500',
            'isi' => 'required|string',
            'aktif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return $validator->validated();
    }
}