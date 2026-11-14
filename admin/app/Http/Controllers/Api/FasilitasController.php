<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fasilitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FasilitasController extends Controller
{
    public function index()
    {
        $fasilitas = Fasilitas::all();
        return response()->json([
            'success' => true,
            'data' => $fasilitas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'foto_fasilitas' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $fasilitas = new Fasilitas();
        $fasilitas->nama = $request->nama;
        $fasilitas->deskripsi = $request->deskripsi;

        if ($request->hasFile('foto_fasilitas')) {
            $path = $request->file('foto_fasilitas')->store('fasilitas', 'public');
            $fasilitas->foto_fasilitas = $path;
        }

        $fasilitas->save();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil ditambahkan',
            'data' => $fasilitas
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $fasilitas = Fasilitas::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'foto_fasilitas' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $fasilitas->nama = $request->nama;
        $fasilitas->deskripsi = $request->deskripsi;

        // Jika tombol Hapus Gambar diklik di admin
        if ($request->has('remove_foto') && $request->remove_foto == '1') {
            if ($fasilitas->foto_fasilitas && Storage::disk('public')->exists($fasilitas->foto_fasilitas)) {
                Storage::disk('public')->delete($fasilitas->foto_fasilitas);
            }
            $fasilitas->foto_fasilitas = null;
        }

        // Jika mengunggah foto baru
        if ($request->hasFile('foto_fasilitas')) {
            if ($fasilitas->foto_fasilitas && Storage::disk('public')->exists($fasilitas->foto_fasilitas)) {
                Storage::disk('public')->delete($fasilitas->foto_fasilitas);
            }
            $path = $request->file('foto_fasilitas')->store('fasilitas', 'public');
            $fasilitas->foto_fasilitas = $path;
        }

        $fasilitas->save();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil diperbarui',
            'data' => $fasilitas
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
            'message' => 'Fasilitas berhasil dihapus'
        ]);
    }
}