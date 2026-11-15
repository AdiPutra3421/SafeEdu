<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fasilitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FasilitasController extends Controller
{
    public function index()
    {
        $fasilitas = Fasilitas::all();

        return response()->json([
            'success' => true,
            'data' => $fasilitas,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:150',
            'lokasi' => 'nullable|string|max:150',
            'status_kondisi' => 'nullable|in:Bisa Digunakan,Dalam Perbaikan,Tidak Bisa Digunakan',
            'deskripsi' => 'nullable|string',
            'foto_fasilitas' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        unset($validated['foto_fasilitas']);

        $fasilitas = new Fasilitas;
        $fasilitas->fill($validated);

        if ($request->hasFile('foto_fasilitas')) {
            $path = $request->file('foto_fasilitas')->store('fasilitas', 'public');
            if ($path === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto fasilitas gagal disimpan. Silakan coba kembali.',
                ], 500);
            }
            $fasilitas->foto_fasilitas = $path;
        }

        $fasilitas->save();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil ditambahkan',
            'data' => $fasilitas,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $fasilitas = Fasilitas::find($id);
        if (! $fasilitas) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:150',
            'lokasi' => 'sometimes|nullable|string|max:150',
            'status_kondisi' => 'sometimes|required|in:Bisa Digunakan,Dalam Perbaikan,Tidak Bisa Digunakan',
            'deskripsi' => 'nullable|string',
            'foto_fasilitas' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        unset($validated['foto_fasilitas']);
        $fasilitas->fill($validated);

        if ($request->hasFile('foto_fasilitas')) {
            $path = $request->file('foto_fasilitas')->store('fasilitas', 'public');
            if ($path === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto fasilitas gagal disimpan. Silakan coba kembali.',
                ], 500);
            }
            if ($fasilitas->foto_fasilitas && Storage::disk('public')->exists($fasilitas->foto_fasilitas)) {
                Storage::disk('public')->delete($fasilitas->foto_fasilitas);
            }
            $fasilitas->foto_fasilitas = $path;
        } elseif ($request->input('remove_foto') === '1') {
            if ($fasilitas->foto_fasilitas && Storage::disk('public')->exists($fasilitas->foto_fasilitas)) {
                Storage::disk('public')->delete($fasilitas->foto_fasilitas);
            }
            $fasilitas->foto_fasilitas = null;
        }

        $fasilitas->save();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil diperbarui',
            'data' => $fasilitas,
        ]);
    }

    public function destroy($id)
    {
        $fasilitas = Fasilitas::findOrFail($id);

        if ($fasilitas->foto_fasilitas && Storage::disk('public')->exists($fasilitas->foto_fasilitas)) {
            Storage::disk('public')->delete($fasilitas->foto_fasilitas);
        }

        $fasilitas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil dihapus',
        ]);
    }
}
