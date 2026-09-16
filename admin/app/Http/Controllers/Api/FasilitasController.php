<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fasilitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FasilitasController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Fasilitas::orderBy('id', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:150',
            'lokasi' => 'required|string|max:150',
            'status_kondisi' => 'required|string',
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fasilitas = Fasilitas::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil ditambahkan',
            'data' => $fasilitas
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $fasilitas = Fasilitas::find($id);
        if (!$fasilitas) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:150',
            'lokasi' => 'required|string|max:150',
            'status_kondisi' => 'required|string',
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fasilitas->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil diupdate',
            'data' => $fasilitas
        ]);
    }

    public function destroy($id)
    {
        $fasilitas = Fasilitas::find($id);
        if (!$fasilitas) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $fasilitas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil dihapus'
        ]);
    }
}
