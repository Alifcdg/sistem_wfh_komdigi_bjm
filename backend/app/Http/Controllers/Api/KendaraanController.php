<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kendaraan;
use Illuminate\Http\Request;

class KendaraanController extends Controller
{
    // Menampilkan seluruh data kendaraan
    public function index()
    {
        $kendaraan = Kendaraan::with('pegawais')->get();

        return response()->json([
            'message' => 'Data kendaraan berhasil diambil.',
            'data' => $kendaraan,
        ], 200);
    }

    // Menambahkan data kendaraan
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kendaraan' => 'required|string|max:255',
            'jenis_kendaraan' => 'required|string|max:100',
            'jenis_bbm' => 'nullable|string|max:100',
            'efisiensi_km_per_liter' => 'nullable|numeric|gt:0',
            'sumber_efisiensi' => 'nullable|string|max:255',
        ]);

        $kendaraan = Kendaraan::create($validated);

        return response()->json([
            'message' => 'Data kendaraan berhasil ditambahkan.',
            'data' => $kendaraan,
        ], 201);
    }

    // Menampilkan detail kendaraan berdasarkan ID
    public function show(int $id)
    {
        $kendaraan = Kendaraan::with('pegawais')->findOrFail($id);

        return response()->json([
            'message' => 'Detail kendaraan berhasil diambil.',
            'data' => $kendaraan,
        ], 200);
    }

    // Memperbarui data kendaraan
    public function update(Request $request, int $id)
    {
        $kendaraan = Kendaraan::findOrFail($id);

        $validated = $request->validate([
            'nama_kendaraan' => 'required|string|max:255',
            'jenis_kendaraan' => 'required|string|max:100',
            'jenis_bbm' => 'nullable|string|max:100',
            'efisiensi_km_per_liter' => 'nullable|numeric|gt:0',
            'sumber_efisiensi' => 'nullable|string|max:255',
        ]);

        $kendaraan->update($validated);

        return response()->json([
            'message' => 'Data kendaraan berhasil diperbarui.',
            'data' => $kendaraan,
        ], 200);
    }

    // Menghapus data kendaraan
    public function destroy(int $id)
    {
        $kendaraan = Kendaraan::findOrFail($id);

        // Periksa apakah kendaraan masih digunakan pegawai
        if ($kendaraan->pegawais()->exists()) {
            return response()->json([
                'message' => 'Kendaraan tidak dapat dihapus karena masih digunakan oleh pegawai.',
            ], 409);
        }

        $kendaraan->delete();

        return response()->json([
            'message' => 'Data kendaraan berhasil dihapus.',
        ], 200);
    }
}
