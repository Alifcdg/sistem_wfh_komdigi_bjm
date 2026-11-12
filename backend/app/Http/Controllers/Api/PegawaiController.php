<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PegawaiController extends Controller
{
    // Menampilkan seluruh data pegawai
    public function index()
    {
        $pegawai = Pegawai::with('kendaraan')->get();

        return response()->json([
            'message' => 'Data pegawai berhasil diambil.',
            'data' => $pegawai,
        ], 200);
    }

    // Menambahkan data pegawai
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('pegawais', 'nip'),
            ],
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'alamat_rumah' => 'nullable|string',
            'jarak_kantor_km' => 'nullable|numeric|min:0',
            'kendaraan_id' => 'nullable|exists:kendaraans,id_kendaraan',
            'status_pegawai' => 'required|in:Aktif,Tidak Aktif',
        ]);

        $pegawai = Pegawai::create($validated);

        return response()->json([
            'message' => 'Data pegawai berhasil ditambahkan.',
            'data' => $pegawai,
        ], 201);
    }

    // Menampilkan detail pegawai berdasarkan ID
    public function show(int $id)
    {
        $pegawai = Pegawai::with('kendaraan')->findOrFail($id);

        return response()->json([
            'message' => 'Detail pegawai berhasil diambil.',
            'data' => $pegawai,
        ], 200);
    }

    // Memperbarui data pegawai
    public function update(Request $request, int $id)
    {
        $pegawai = Pegawai::findOrFail($id);

        $validated = $request->validate([
            'nip' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('pegawais', 'nip')
                    ->ignore($pegawai->id_pegawai, 'id_pegawai'),
            ],
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'alamat_rumah' => 'nullable|string',
            'jarak_kantor_km' => 'nullable|numeric|min:0',
            'kendaraan_id' => 'nullable|exists:kendaraans,id_kendaraan',
            'status_pegawai' => 'required|in:Aktif,Tidak Aktif',
        ]);

        $pegawai->update($validated);

        return response()->json([
            'message' => 'Data pegawai berhasil diperbarui.',
            'data' => $pegawai,
        ], 200);
    }

    // Mengubah status pegawai dan akun pengguna
    public function updateStatus(Request $request, int $id)
    {
        $pegawai = Pegawai::findOrFail($id);

        $validated = $request->validate([
            'status_pegawai' => 'required|in:Aktif,Tidak Aktif',
        ]);

        DB::transaction(function () use ($pegawai, $validated) {
            $pegawai->update($validated);

            if ($pegawai->user) {
                $statusAkun = $validated['status_pegawai'] === 'Aktif'
                    ? 'Aktif'
                    : 'Tidak Aktif';

                $pegawai->user->update([
                    'status_akun' => $statusAkun,
                ]);
            }
        });

        return response()->json([
            'message' => 'Status pegawai dan akun berhasil diperbarui.',
            'data' => $pegawai->fresh()->load('user'),
        ], 200);
    }
}
