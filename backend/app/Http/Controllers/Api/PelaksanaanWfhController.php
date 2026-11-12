<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PelaksanaanWfh;
use Illuminate\Support\Facades\Auth;

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
}
