<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MonitoringWfhService;
use Illuminate\Http\Request;

class MonitoringWfhController extends Controller
{
    protected MonitoringWfhService $monitoringService;

    public function __construct(
        MonitoringWfhService $monitoringService
    ) {
        $this->monitoringService = $monitoringService;
    }

    public function ringkasan(Request $request)
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => [
                'required',
                'date',
                'after_or_equal:tanggal_mulai',
            ],
        ]);

        $user = $request->user();
        $namaRole = $user?->role?->nama_role;

        // Menentukan cakupan data berdasarkan role.
        // Guest dapat melihat ringkasan seluruh pegawai.
        $pegawaiId = null;

        if ($user && $namaRole === 'Pegawai') {
            if (!$user->pegawai_id) {
                return response()->json([
                    'message' => 'Akun belum terhubung dengan data pegawai.',
                ], 403);
            }

            $pegawaiId = $user->pegawai_id;
        } elseif (
            $user &&
            !in_array($namaRole, ['Admin', 'Kepala Balai'])
        ) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses.',
            ], 403);
        }

        $dataWfh = $this->monitoringService->ambilDataWfh(
            $validated['tanggal_mulai'],
            $validated['tanggal_selesai'],
            $pegawaiId
        );

        $hasil = $this->monitoringService->hitungRingkasan(
            $dataWfh
        );

        return response()->json([
            'message' => 'Ringkasan monitoring WFH berhasil diambil.',
            'periode' => [
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
            ],
            'data' => $hasil,
        ]);
    }
}
