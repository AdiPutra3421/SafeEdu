<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KontenSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('konten', 'public');
            $validated['gambar'] = $path;
        } elseif ($request->filled('gambar_url')) {
            $validated['gambar'] = $request->gambar_url;
        }

        $validated['aktif'] = $request->has('aktif') ? $request->boolean('aktif') : true;

        $konten = KontenSekolah::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil ditambahkan.',
            'data' => $konten,
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

        if ($request->hasFile('gambar')) {
            // Delete old file if exists
            if ($content->gambar && Storage::disk('public')->exists($content->gambar)) {
                Storage::disk('public')->delete($content->gambar);
            }
            $path = $request->file('gambar')->store('konten', 'public');
            $validated['gambar'] = $path;
        } elseif ($request->boolean('remove_gambar')) {
            if ($content->gambar && Storage::disk('public')->exists($content->gambar)) {
                Storage::disk('public')->delete($content->gambar);
            }
            $validated['gambar'] = null;
        } elseif ($request->filled('gambar_url')) {
            $validated['gambar'] = $request->gambar_url;
        }

        if ($request->has('aktif')) {
            $validated['aktif'] = $request->boolean('aktif');
        }

        $content->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil diperbarui.',
            'data' => $content,
        ]);
    }

    public function destroy($id)
    {
        $content = KontenSekolah::find($id);
        if (! $content) {
            return response()->json(['success' => false, 'message' => 'Konten tidak ditemukan.'], 404);
        }

        if ($content->gambar && Storage::disk('public')->exists($content->gambar)) {
            Storage::disk('public')->delete($content->gambar);
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
            'gambar' => 'nullable|file|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'gambar_url' => 'nullable|string|max:500',
            'aktif' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        unset($validated['gambar'], $validated['gambar_url']);

        return $validated;
    }
}