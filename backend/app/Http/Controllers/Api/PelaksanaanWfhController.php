<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PelaksanaanWfh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PelaksanaanWfhController extends Controller
{
    // Menampilkan seluruh data pelaksanaan WFH untuk Admin
    public function indexAdmin()
    {
        $pelaksanaan = PelaksanaanWfh::with('pegawai')
            ->latest('tanggal_wfh')
            ->get();

        return response()->json([
            'message' => 'Seluruh data pelaksanaan WFH berhasil diambil.',
            'data' => $pelaksanaan,
        ], 200);
    }

    // Menampilkan data pelaksanaan WFH milik Pegawai yang sedang login
    public function indexPegawai()
    {
        $pegawai = Auth::user()->pegawai;

        if (!$pegawai) {
            return response()->json([
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        $pelaksanaan = PelaksanaanWfh::where('pegawai_id', $pegawai->id_pegawai)
            ->latest('tanggal_wfh')
            ->get();

        return response()->json([
            'message' => 'Data pelaksanaan WFH berhasil diambil.',
            'data' => $pelaksanaan,
        ], 200);
    }

    // Konfirmasi realisasi WFH oleh Pegawai
    public function konfirmasi(Request $request, $id)
    {
        $request->validate([
            'status_pelaksanaan' => 'required|in:Terlaksana,Tidak Terlaksana',
        ]);

        $user = $request->user();
        $pegawai = $user->pegawai;

        if (!$pegawai) {
            return response()->json([
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        $pelaksanaan = DB::transaction(function () use ($id, $pegawai, $user, $request) {

            $data = PelaksanaanWfh::where('id_pelaksanaan', $id)
                ->where('pegawai_id', $pegawai->id_pegawai)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                return response()->json([
                    'message' => 'Data pelaksanaan WFH tidak ditemukan.',
                ], 404);
            }

            if ($data->status_pelaksanaan !== 'Terjadwal') {
                return response()->json([
                    'message' => 'Jadwal WFH ini sudah dikonfirmasi sebelumnya.',
                ], 422);
            }

           // Konfirmasi hanya dapat dilakukan setelah tanggal WFH berakhir
        $mulaiKonfirmasi = $data->tanggal_wfh
            ->copy()
            ->addDay()
            ->startOfDay();

        if (now()->lessThan($mulaiKonfirmasi)) {
            return response()->json([
                'message' => 'Konfirmasi hanya dapat dilakukan setelah tanggal WFH berakhir.',
            ], 422);
        }

        // Batas akhir konfirmasi adalah pukul 23.59 pada hari berikutnya
        $batasKonfirmasi = $data->tanggal_wfh
            ->copy()
            ->addDay()
            ->endOfDay();

        if (now()->greaterThan($batasKonfirmasi)) {
            return response()->json([
                'message' => 'Batas waktu konfirmasi WFH telah berakhir.',
            ], 422);
        }

            $data->update([
                'status_pelaksanaan' => $request->status_pelaksanaan,
                'tanggal_konfirmasi' => now(),
                'dikonfirmasi_oleh' => $user->id_user,
            ]);

            return $data->fresh();
        });

        if ($pelaksanaan instanceof \Illuminate\Http\JsonResponse) {
            return $pelaksanaan;
        }

        return response()->json([
            'message' => 'Konfirmasi pelaksanaan WFH berhasil disimpan.',
            'data' => $pelaksanaan,
        ], 200);
    }
}
