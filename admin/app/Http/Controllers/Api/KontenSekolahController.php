<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KontenSekolah;
use Illuminate\Http\JsonResponse;
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
        if ($validated instanceof JsonResponse) {
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
        if ($validated instanceof JsonResponse) {
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

    private function validateContent(Request $request): array|JsonResponse
    {
        $isBerita = $request->input('tipe') === 'berita';
        $judulRules = ['required', 'string', 'max:180'];

        if ($isBerita) {
            $judulRules[] = 'min:10';
            $judulRules[] = 'regex:/^(?=.*\p{L})[\p{L}\p{N} .,!?‘():\/-]+$/u';
        }

        $judulRules[] = function ($attribute, $value, $fail) {
            if (is_string($value) && $this->isLikelyRandomText($value)) {
                $fail('Judul harus berupa teks yang jelas dan bukan input acak.');
            }
        };

        $judulRules[] = function ($attribute, $value, $fail) use ($request) {
            if (! is_string($value)) {
                return;
            }

            $duplicates = KontenSekolah::query()->where('judul', $value);
            if ($request->route('id')) {
                $duplicates->where('id', '!=', $request->route('id'));
            }

            if ($duplicates->get(['judul'])->contains(fn ($content) => $content->judul === $value)) {
                $fail('Judul konten sudah digunakan.');
            }
        };

        $ringkasanRules = $isBerita
            ? ['nullable', 'string', 'min:20', 'max:300', function ($attribute, $value, $fail) {
                if (! is_string($value)) {
                    return;
                }

                if (trim($value) === '') {
                    $fail('Ringkasan tidak boleh hanya berisi spasi.');
                }
            }]
            : ['nullable', 'string', 'max:500'];

        $isiRules = ['required', 'string', function ($attribute, $value, $fail) {
            if (is_string($value) && $this->isLikelyRandomText($value)) {
                $fail('Isi konten harus berupa teks yang jelas dan bukan input acak.');
            }
        }];
        if ($isBerita) {
            $isiRules[] = 'min:50';
            $isiRules[] = function ($attribute, $value, $fail) {
                if (! is_string($value)) {
                    return;
                }

                if (mb_strlen(trim($value)) < 50) {
                    $fail('Isi konten berita minimal 50 karakter selain spasi.');
                }

                if (preg_match('/<\s*\/?\s*[a-z][^>]*>|<\?|javascript\s*:|on[a-z]+\s*=|\b(?:eval|function)\s*\(|\b(?:const|let|var)\s+\w+\s*=|\b(?:document|window|console)\s*\./iu', $value)) {
                    $fail('Isi konten tidak boleh memuat kode atau script.');
                }
            };
        }

        $validator = Validator::make($request->all(), [
            'tipe' => 'required|in:berita,panduan',
            'judul' => $judulRules,
            'ringkasan' => $ringkasanRules,
            'isi' => $isiRules,
            'gambar' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gambar_url' => $isBerita ? 'prohibited' : 'nullable|string|max:500',
            'aktif' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        unset($validated['gambar'], $validated['gambar_url']);

        return $validated;
    }

    private function isLikelyRandomText(string $value): bool
    {
        $text = mb_strtolower(trim($value));

        if (preg_match('/(.{2,})\1{2,}/iu', $text) || preg_match('/(\p{L})\1{3,}/iu', $text)) {
            return true;
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $text, $wordMatches);
        if (array_filter(array_count_values($wordMatches[0]), fn ($count) => $count >= 3)) {
            return true;
        }

        preg_match_all('/\p{L}/u', $text, $letterMatches);
        preg_match_all('/[aeiouáéíóú]/iu', $text, $vowelMatches);
        $letterCount = count($letterMatches[0]);

        return $letterCount >= 10 && count($vowelMatches[0]) / $letterCount < 0.2;
    }
}
